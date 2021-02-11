<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Xml\Loader;

class TranslationFilesTest extends TestCase
{
    /**
     * @dataProvider provideTranslationFiles
     */
    public function testTranslationFileIsValid($filePath)
    {
        $loader = class_exists(Loader::class)
            ? [new Loader(), 'loadFile']
            : ['PHPUnit\Util\XML', 'loadfile'];

        $loader($filePath, false, false, true);

        $this->addToAssertionCount(1);
    }

    public function provideTranslationFiles()
    {
        return array_map(
            function ($filePath) { return (array) $filePath; },
            glob(\dirname(\dirname(__DIR__)).'/Resources/translations/*.xlf')
        );
    }

    public function testNorwegianAlias()
    {
        $this->assertFileEquals(
            \dirname(\dirname(__DIR__)).'/Resources/translations/security.nb.xlf',
            \dirname(\dirname(__DIR__)).'/Resources/translations/security.no.xlf',
            'The NO locale should be an alias for the NB variant of the Norwegian language.'
        );
    }
}
