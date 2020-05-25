<?php

class GetServerService
{
    public $deployment_servers;

    public function __construct()
    {
        $this->deployment_servers = $this->getServers();
    }

    public function getServers()
    {

         return [
            'bubwise.staging'=> [ 
                "location" => '/var/www/html/bubwiseapp',
                "connection" => 'deploy@45.77.233.21'],
            'bubwise.prod'=> [ 
                "location" => '/var/www/html/bubwiseapp',
                "connection" => 'deploy@45.63.31.154'],
            'bubwise-vue.prod'=> [ 
                "location" => '/var/www/html/bubwise-vue',
                "connection" => 'deploy@45.63.31.154'],                
            'startmycompany.com.au'=> [ 
                "location" => '/var/www/html/startmycompany.com.au',
                "connection" => 'deploy@45.77.233.21'],
         ];
    }

    /*
     * get the server details required by its domain provided.
     * */
    public function getServerSettings($url_name)
    {
        $serverSetting = null;
        foreach ($this->deployment_servers as $server_name => $server) {
            if ($server_name == $url_name) {
                $serverSetting = [
                    'url' => $server_name ,
                    'location' => $server['location'],
                    'connection' => $server['connection']
                ];
                break;
            }
        }
        return $serverSetting;
    }

    /*
     * Get all the servers to assign to the @servers in envoy.blade.php
     * @return array
     * */
    public function getServersArray()
    {
        $serversArray = array_map(function ($item) {
            return $item['connection'];
        }, $this->deployment_servers);
        return $serversArray;
    }
    

}
