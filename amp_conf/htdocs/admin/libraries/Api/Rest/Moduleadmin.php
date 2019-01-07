<?php

namespace FreePBX\Api\Rest;
use FreePBX\modules\Api\Rest\Base;
use Symfony\Component\Process\Process;

class Moduleadmin extends Base {
    public static function getScopes() {
        /*
         * Once upon a time in a land far away a developer started this for the purpose of
         * realtime interaction in the UI. He realized this would require a dependency needed
         * to install that dependency causing the universe to implode. The original task needed
         * to be completed so this code is left for future development and testing as it will be
         * very useful to have in place on a machine that has the api module installes. 
         * If you are reading this you could be the Hero that completes this quest. 
         * Even the smallest person can change the course of the future. J.R.R ~Tolkien
         */
        return [];
        /*
        return [
            'read:modulelist' => [
                'description' => _('Read the list of module data or individual module data'),
            ],
            'write:install' => [
                'description' => _('Ability to install module(s)'),
            ],
            'write:installremote' => [
                'description' => _('Ability to install module(s) by URL'),
            ],
            'write:uninstall' => [
                'description' => _('Ability to uninstall module(s)'),
            ],
            'write:disable' => [
                'description' => _('Ability to disable module(s)'),
            ],
            'write:enable' => [
                'description' => _('Ability to enable module(s)'),
            ],
            'write:delete' => [
                'description' => _('Ability to delete module(s)'),
            ],
            'read:logs' => [
                'description' => _('Ability to read install logs'),
            ],
        ];
        */
    }
    public function setupRoutes($app) {
        $this->fwconsole = $this->freepbx->Config->get('AMPSBIN').'/fwconsole';
        $freepbx = $this->freepbx;
        $parent = $this;

        $app->get('/moduleinfo[/{modules}]', function ($request, $response, $args) use ($parent, $freepbx) {
        if(empty($args['modules']) || !isset($args['modules'])){
            $modules = json_encode([]);
        }
        $modules = json_decode($modules, true);
        $error = json_last_error();
        if($error !== JSON_ERROR_NONE){
            $data = $parent->processJSONError($error);
            return $response->withJson($data, 400);
        }
        if(empty($modules)){
            $data = $freepbx->Modules->getInfo();
        }
        if(!empty($modules)){
            $final = [];
            foreach($modules as $module){
                if(!isset($module['rawname']) || empty($module['rawname'])){
                    continue;
                }
                $final[$module['rawname']] = $freepbx->Modules->getInfo($module['rawname']);
            }
            $data = $final;
        }

        return $response->withJson($data);
        })->add($this->checkWriteScopeMiddleware('modulelist'));

        $app->post('/installmodule', function ($request, $response) use ($parent, $freepbx) {
            $modules = $request->post('modules');
            $force = $request->post('force');
            if(empty($modules) || !isset($modules)){
                return $response->withJson(['status' => false, 'message' => _("You must provide one or more module(s) info")], 400);
            }
            $modules = json_decode($modules,true);
            $error = json_last_error();
            if($error !== JSON_ERROR_NONE){
                $data = $parent->processJSONError($error);
                return $response->withJson($data, 400);
            }
            $single = false;
            if(count($modules) === 1){
                $single = true;
            }
            foreach ($modules as $module) {       
                if(!isset($module['rawname']) || empty($module['rawname'])){
                    continue;
                }
                try{
                    $conflicts = $freepbx->Modules->checkBreaking($module);
                }catch(\Exception $e){
                    $conflicts = ['status' => true, 'issues' => [$e->getMessage()]];
                }
                if(!isset($conflcts['issues']) || !empty($conflcts['issues']) && !$force &&  !$single){
                    return $response->withJson($conflcts);
                }
                
                $rawname = $module['rawname'];
                if(isset($module['version']) || !empty($module['version '])){
                    $rawname = $rawname.':'.$module['version'];
                }
                $command = [
                    $this->fwconsole,
                    'ma',
                    'install',
                    '--quiet'
                ];
                if($force){
                    $command[] = '--force';
                }
                $command[] = $rawname;
            }

            $process = new Process($command);
            $process->disableOutput();
            $process->start();
            $pid = $process->getPid();
            if($process->isRunning){
                return $response->withJson(['status' => true, 'message' => _("Install running"), 'pid' => $pid]);
            }
            return $response->withJson(['status' => false, 'message' => _("The install process failed to start, you may wish to retry.")]);

        })->add($this->checkWriteScopeMiddleware('install'));
 
        $app->post('/updatemodule', function ($request, $response) use ($parent, $freepbx) {
            $modules = $request->post('modules');
            $force = $request->post('force');
            if(empty($modules) || !isset($modules)){
                return $response->withJson(['status' => false, 'message' => _("You must provide one or more module(s) info")], 400);
            }
            $modules = json_decode($modules,true);
            $error = json_last_error();
            if($error !== JSON_ERROR_NONE){
                $data = $parent->processJSONError($error);
                return $response->withJson($data, 400);
            }
            $single = false;
            if(count($modules) === 1){
                $single = true;
            }
            foreach ($modules as $module) {       
                if(!isset($module['rawname']) || empty($module['rawname'])){
                    continue;
                }
                $ret = $this->install($module);
                try{
                    $conflicts = $freepbx->Modules->checkBreaking($module);
                }catch(\Exception $e){
                    $conflicts = ['status' => true, 'issues' => [$e->getMessage()]];
                }
                if(!isset($conflcts['issues']) || !empty($conflcts['issues']) && !$force &&  !$single){
                    return $response->withJson($conflcts);
                }
                
                $rawname = $module['rawname'];
                if(isset($module['version']) || !empty($module['version '])){
                    $rawname = $rawname.':'.$module['version'];
                }
                $command = [
                    $this->fwconsole,
                    'ma',
                    'update',
                    '--quiet'
                ];
                if($force){
                    $command[] = '--force';
                }
                $command[] = $rawname;
            }

            $process = new Process($command);
            $process->disableOutput();
            $process->start();
            $pid = $process->getPid();
            if($process->isRunning()){
                return $response->withJson(['status' => true, 'message' => _("Update running"), 'pid' => $pid]);
            }
            return $response->withJson(['status' => false, 'message' => _("The update process failed to start, you may wish to retry.")]);

        })->add($this->checkWriteScopeMiddleware('install'));

        $app->get('/log', function ($request, $response, $args) use ($parent, $freepbx) {
            $logid = $request->post('logid');
            $log = $parent->getLog($logid);
            //This only looks bad. Signal 0 just returns (bool) running or not.
            $running = posix_kill($logid, 0);
            $final = [];
            foreach ($log as $key => $value) {
                if(isset($value['entry']) && !empty($value['entry'])){
                    $final[] = $value['entry'];
                }
            }
            $parent->cleanLogs();
            return $response->withJson(['status' => $running, 'logdata' => implode(PHP_EOL,$final)]);
        })->add($this->checkReadScopeMiddleware('logs'));
        

    }
    /**
     * Removes log entries over 1 hour old.
     *
     * @return current object
     */
    public function cleanLogs(){
        $logs = $this->freepbx->getAll('installLogs');
        if(!empty($logs)){
            foreach ($logs as $key => $logitem) {
                $oneHour = 3600;
                $timestamp = $logitem['timestamp'] + $oneHour;
                if(time() > $timestamp){
                    $this->freepbx->delConfig($key, 'installLogs');
                }
            }
        }
        return $this;
    }
    
    /**
     * Returns log entries for a given pid
     *
     * @param int $logid
     * @return array Log items an array of arrays.
     */
    public function getLog($logid){
        $logs = $this->freepbx->getAll('installLogs');
        $final = [];
        foreach ($logs as $key => $logitem) {
            if($logitem['pid'] == $logid){
                $final[] = $logitem;
            }
        }
        return $final;
    }

    /**
     * Returns a human friendly error
     *
     * @param int $error
     * @return array with ststus, message, return code 
     */
    public function processJSONError($error){
        $message = _("Unknown JSON Error");
        if($error == JSON_ERROR_DEPTH){
            $message = _("JSON stack length exceeded, try sending less data");
        }
        if($error == JSON_ERROR_STATE_MISMATCH){
            $message = _("JSON is invalid or malformed");
        }
        if($error == JSON_ERROR_CTRL_CHAR){
            $message = _("JSON contains illegal control character, check syntax");
        }
        if($error == JSON_ERROR_SYNTAX){
            $message = _("JSON invalid, check syntax");
        }
        if($error == JSON_ERROR_UTF8){
            $message = _("JSON contains bad UTF8 characters, check syntax");
        }
        if($error == JSON_ERROR_RECURSION){
            $message = _("JSON contains one or more recursive references, check syntax");
        }
        if($error == JSON_ERROR_INF_OR_NAN){
            $message = _("JSON contains null values, check syntax");
        }
        if($error == JSON_ERROR_UNSUPPORTED_TYPE){
            $message = _("JSON contains an unsupported value type");
        }
        if($error == JSON_ERROR_INVALID_PROPERTY_NAME){
            $message = _("JSON contains a bad property name and cannot continue, check properties");
        }

        return [
            'status' => false,
            'message' => $message,
            'errorcode' => $error,
        ];
    }

}