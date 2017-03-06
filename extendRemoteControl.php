<?php
/**
 * Demo plugin to show how to extendRemoteControl Plugin for LimeSurvey.
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2015-2016 Denis Chenu <http://sondages.pro>
 * @license GPL v3
 * @version 1.1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

class extendRemoteControl extends \ls\pluginmanager\PluginBase {
    protected $storage = 'DbStorage';
    static protected $description = 'Allow to add function to remoteControl';
    static protected $name = 'extendRemoteControl';

    protected $settings = array(
        'information' => array(
            'type' => 'info',
            'content' => '',
            'default'=> false
        ),
    );

    public function init()
    {
        $this->subscribe('newDirectRequest');
        $this->subscribe('newUnsecureRequest','newDirectRequest');
    }

    /**
     * The access is done here for remoteControl access with plugin
     * @see remotecontrol::run()
     */
    public function newDirectRequest()
    {
        $oEvent = $this->getEvent();
        if ($oEvent->get('target') != $this->getName())
            return;
        $action = $oEvent->get('function');

        $oAdminController = new \AdminController('admin/remotecontrol');
        Yii::import('application.helpers.remotecontrol.*');
        Yii::setPathOfAlias('extendRemoteControl', dirname(__FILE__));
        Yii::import("extendRemoteControl.RemoteControlHandler");
        $oHandler=new \RemoteControlHandler($oAdminController);
        $RPCType=Yii::app()->getConfig("RPCInterface");
        if($RPCType!='json')
        {
            header("Content-type: application/json");
            echo json_encode(array(
                'status'=>'error',
                'message'=>'Only for json RPCInterface',
            ));
            Yii::app()->end();
        }
        if (Yii::app()->request->getIsPostRequest())
        {
            Yii::app()->loadLibrary('LSjsonRPCServer');
            if (!isset($_SERVER['CONTENT_TYPE']))
            {
                $serverContentType = explode(';', $_SERVER['HTTP_CONTENT_TYPE']);
                $_SERVER['CONTENT_TYPE'] = reset($serverContentType);
            }
            LSjsonRPCServer::handle($oHandler);
        }
        elseif(Yii::app()->getConfig("rpc_publish_api") == true) // Show like near Core LS do it
        {
            $reflector = new ReflectionObject($oHandler);
            foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                /* @var $method ReflectionMethod */
                if (substr($method->getName(),0,1) !== '_') {
                    $list[$method->getName()] = array(
                        'description' => str_replace(array("\r", "\r\n", "\n"), "<br/>", $method->getDocComment()),
                        'parameters'  => $method->getParameters()
                    );
                }
            }
            ksort($list);
            $aData['method'] = $RPCType;
            $aData['list'] = $list;

            $version=floatval(Yii::app()->getConfig("versionnumber"));
            if($version<2.5)
            {
                $content=$oAdminController->renderPartial('application.views.admin.remotecontrol.index_view',$aData,true);
                $oEvent->setContent($this, $content);
            }
            else // Show something for 2.5, but 2.5 have a better system
            {
                return $oAdminController->render('application.views.admin.remotecontrol.index_view',$aData);
            }
        }
    }
    /**
     * Update the information content to show the good link
     * @params getValues
     */
    public function getPluginSettings($getValues=true)
    {
        $this->settings['information']['content']="";
        /* test if plugins/unsecure is in noCsrfValidationRoutes : in internal for compatible LimeSurvey version */
        if(in_array('plugins/unsecure',App()->request->noCsrfValidationRoutes))
        {
            $url=$this->api->createUrl('plugins/unsecure', array('plugin' => $this->getName(), 'function' => 'action'));
        }
        else
        {
            $this->settings['information']['content'].="<p class='alert alert-warning'>You need to add 'plugins/direct' to noCsrfValidationRoutes in your config file</p>";
            $url=$this->api->createUrl('plugins/direct', array('plugin' => $this->getName(), 'function' => 'action'));
        }
        if(Yii::app()->getConfig("RPCInterface")=='json')
        {
            $this->settings['information']['content'].="<p class='alert alert-info'>".sprintf(gT("The remote url was <code>%s</code>",'unescaped'),$url)."</p>";
            if(Yii::app()->getConfig("rpc_publish_api") == true)
            {
                if(floatval(Yii::app()->getConfig("versionnumber"))>=2.5)
                {
                    $url=$this->api->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody','method'=>'actionIndex','surveyId'=>0));
                }
                $this->settings['information']['content'].="<p class='alert alert-warning'>".sprintf(gT("The API was published on <a href='%s'>%s</a>",'unescaped'),$url,$url)."</p>";
            }
        }
        else
        {
            $this->settings['information']['content']="<p class='alert alert-danger'>".gT("JSON RPC is not active.")."</p>";
        }
        return parent::getPluginSettings($getValues);
    }

    /**
     * Show remote control function list in 2.50
     * Used by PluginHelper->getContent
     * @see remotecontrol::run()
     */
    public function actionIndex()
    {
        if(Yii::app()->getConfig("RPCInterface")=='json' && Yii::app()->getConfig("rpc_publish_api"))
        {
            $oAdminController = new \AdminController('admin/remotecontrol');
            Yii::import('application.helpers.remotecontrol.*');
            Yii::setPathOfAlias('extendRemoteControl', dirname(__FILE__));
            Yii::import("extendRemoteControl.RemoteControlHandler");
            $oHandler=new \RemoteControlHandler($oAdminController);
            $reflector = new ReflectionObject($oHandler);
            foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                /* @var $method ReflectionMethod */
                if (substr($method->getName(),0,1) !== '_') {
                    $list[$method->getName()] = array(
                        'description' => "<pre>".$method->getDocComment()."</pre>",
                        'parameters'  => $method->getParameters()
                    );
                }
            }
            ksort($list);
            $aData['method'] = 'json';
            $aData['list'] = $list;
            return Yii::app()->controller->renderPartial('application.views.admin.remotecontrol.index_view', $aData, true);
        }
    }


}
