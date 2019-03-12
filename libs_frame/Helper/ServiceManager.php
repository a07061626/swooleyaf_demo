<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2018/7/21 0021
 * Time: 9:27
 */
namespace Helper;

use Tool\Tool;

class ServiceManager {
    public static function handleAllService(string $commandPrefix,array $projects){
        $action = Tool::getClientOption('-s', false, '');
        switch ($action) {
            case 'start-all' :
                foreach ($projects as $eProject) {
                    foreach ($eProject['listens'] as $eListen) {
                        $command = $commandPrefix . ' -s start -module ' . $eProject['module_name'] . ' -port ' . $eListen['port'] . ' && ' . $commandPrefix . ' -s startstatus -module ' . $eProject['module_name'] . ' -port ' . $eListen['port'];
                        system($command);
                    }
                }
                break;
            case 'stop-all' :
                foreach ($projects as $eProject) {
                    foreach ($eProject['listens'] as $eListen) {
                        $command = $commandPrefix . ' -s stop -module ' . $eProject['module_name'] . ' -port ' . $eListen['port'];
                        system($command);
                    }
                }
                break;
            case 'restart-all' :
                foreach ($projects as $eProject) {
                    foreach ($eProject['listens'] as $eListen) {
                        $command = $commandPrefix . ' -s restart -module ' . $eProject['module_name'] . ' -port ' . $eListen['port'] . ' && ' . $commandPrefix . ' -s startstatus -module ' . $eProject['module_name'] . ' -port ' . $eListen['port'];
                        system($command);
                    }
                }
                break;
            default :
                system('echo -e "\e[1;31m command not exist \e[0m"');
        }
    }
}