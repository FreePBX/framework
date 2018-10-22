<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Proxy;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Proxy\Proxy as BaseProxy;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Persisters\BasicEntityPersister;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;

/**
 * This factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Marco Pivetta  <ocramius@gmail.com>
 * @since 2.0
 */
class ProxyFactory extends AbstractProxyFactory
{
    /**
     * @var \Doctrine\ORM\EntityManager The EntityManager this factory is bound to.
     */
    private $em;

    /**
     * @var \Doctrine\ORM\UnitOfWork The UnitOfWork this factory uses to retrieve persisters
     */
    private $uow;

    /**
     * @var string
     */
    private $proxyNs;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>EntityManager</tt>.
     *
     * @param \Doctrine\ORM\EntityManager $em           The EntityManager the new factory works for.
     * @param string                      $proxyDir     The directory to use for the proxy classes. It must exist.
     * @param string                      $proxyNs      The namespace to use for the proxy classes.
     * @param boolean                     $autoGenerate Whether to automatically generate proxy classes.
     */
    public function __construct(EntityManager $em, $proxyDir, $proxyNs, $autoGenerate = false)
    {
        $proxyGenerator = new ProxyGenerator($proxyDir, $proxyNs);

        $proxyGenerator->setPlaceholder('baseProxyInterface', 'Doctrine\ORM\Proxy\Proxy');
        parent::__construct($proxyGenerator, $em->getMetadataFactory(), $autoGenerate);

        $this->em      = $em;
        $this->uow     = $em->getUnitOfWork();
        $this->proxyNs = $proxyNs;

    }

    /**
     * {@inheritDoc}
     */
    protected function skipClass(ClassMetadata $metadata)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        return $metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    protected function createProxyDefinition($className)
    {
        $classMetadata   = $this->em->getClassMetadata($className);
        $entityPersister = $this->uow->getEntityPersister($className);

        return new ProxyDefinition(
            ClassUtils::generateProxyClassName($className, $this->proxyNs),
            $classMetadata->getIdentifierFieldNames(),
            $classMetadata->getReflectionProperties(),
            $this->createInitializer($classMetadata, $entityPersister),
            $this->createCloner($classMetadata, $entityPersister)
        );
    }

    /**
     * Creates a closure capable of initializing a proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param \Doctrine\ORM\Persisters\BasicEntityPersister      $entityPersister
     *
     * @return \Closure
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    private function createInitializer(ClassMetadata $classMetadata, BasicEntityPersister $entityPersister)
    {
        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            return function (BaseProxy $proxy) use ($entityPersister, $classMetadata) {
                $initializer = $proxy->__getInitializer();
                $cloner      = $proxy->__getCloner();

                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);
                $proxy->__wakeup();

                if (null === $entityPersister->load($classMetadata->getIdentifierValues($proxy), $proxy)) {
                    $proxy->__setInitializer($initializer);
                    $proxy->__setCloner($cloner);
                    $proxy->__setInitialized(false);

                    throw new EntityNotFoundException();
                }
            };
        }

        return function (BaseProxy $proxy) use ($entityPersister, $classMetadata) {
            $initializer = $proxy->__getInitializer();
            $cloner      = $proxy->__getCloner();

            $proxy->__setInitializer(null);
            $proxy->__setCloner(null);

            if ($proxy->__isInitialized()) {
                return;
            }

            $properties = $proxy->__getLazyProperties();

            foreach ($properties as $propertyName => $property) {
                if (!isset($proxy->$propertyName)) {
                    $proxy->$propertyName = $properties[$propertyName];
                }
            }

            $proxy->__setInitialized(true);

            if (null === $entityPersister->load($classMetadata->getIdentifierValues($proxy), $proxy)) {
                $proxy->__setInitializer($initializer);
                $proxy->__setCloner($cloner);
                $proxy->__setInitialized(false);

                throw new EntityNotFoundException();
            }
        };
    }

    /**
     * Creates a closure capable of finalizing state a cloned proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param \Doctrine\ORM\Persisters\BasicEntityPersister      $entityPersister
     *
     * @return \Closure
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    private function createCloner(ClassMetadata $classMetadata, BasicEntityPersister $entityPersister)
    {
        return function (BaseProxy $proxy) use ($entityPersister, $classMetadata) {
            if ($proxy->__isInitialized()) {
                return;
            }

            $proxy->__setInitialized(true);
            $proxy->__setInitializer(null);
            $class = $entityPersister->getClassMetadata();
            $original = $entityPersister->load($classMetadata->getIdentifierValues($proxy));

            if (null === $original) {
                throw new EntityNotFoundException();
            }

            foreach ($class->getReflectionClass()->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                if ($class->hasField($propertyName) || $class->hasAssociation($propertyName)) {
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($proxy, $reflectionProperty->getValue($original));
                }
            }
        };
    }
}
