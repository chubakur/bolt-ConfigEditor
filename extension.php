<?php

namespace ConfigEditor;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Translation\Loader as TranslationLoader;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Yaml\Dumper as YamlDumper,
    Symfony\Component\Yaml\Parser as YamlParser,
    Symfony\Component\Yaml\Exception\ParseException;

class Extension extends \Bolt\BaseExtension
{
    private $authorized = false;
    private $backupDir;
    private $translationDir;
    public  $config;

    /**
     * @return array
     */
    public function info()
    {

        return array(
            'name'          => "ConfigEditor",
            'description'   => "a visual config editor",
            'tags'          => array('config', 'editor', 'admin', 'tool'),
            'type'          => "Administrative Tool",
            'author'        => "Andrey Pitko",
            'link'          => "http://artvisio.com",
            'email'         => 'chubakur@gmail.com',
            'version'       => "0.1",

            'required_bolt_version' => "1.4",
            'highest_bolt_version'  => "1.4.3",
            'first_releasedate'     => "2014-04-01",
            'latest_releasedate'    => "2014-04-01"
        );

    }

    public function initialize(){
        $this->config = $this->getConfig();
        $this->app->match($this->config['path'], array($this, 'configEditor'));
        $this->addMenuOption(__('ConfigEditor'), $this->config['path'], "icon-list");
    }

    public function configEditor(){
        $file = BOLT_CONFIG_DIR . '/config.yml';
        if (@!is_readable($file) || !@is_writable($file)) {
            throw new \Exception(
                __("The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                    array('%s' => $file)));
        }
        $this->app['twig.loader.filesystem']->addPath(BOLT_PROJECT_ROOT_DIR."/app/view");
        $this->app['twig.loader.filesystem']->addPath(__DIR__.'/views/', 'ConfigEditor');
        $fb = $this->app['form.factory']->createBuilder('form');
        foreach ( $this->app['config']->get($this->config['parameters']) as $key=>$value ){
            $fb->add($key, 'text', array('required'=>true, 'data'=>$value));
        }
        $fb->add('submit', 'submit');
        $form = $fb->getForm();
        if ( $this->app['request']->isMethod('POST') ){
            $parser = new YamlParser();
            $data = $parser->parse(@file_get_contents($file));
            $arr = explode('/', $this->config['parameters']);
            if ( count($arr) < 2 )
                throw new \Exception('Wrong parameters');
            $sdata = null;
            for ( $i = 1; $i < count($arr); ++$i ){
                $key = $arr[$i];
                $sdata = &$data[$key];
            }
            $form->bind($this->app['request']);
            foreach ($this->app['request']->get('form') as $key=>$value){
                if( $key === "submit" || $key === "_token" )
                    continue;
                $sdata[$key] = $value;
            }
            $dumper = new YamlDumper();
            $dumper->setIndentation(2);
            $yaml = $dumper->dump($data, 9999);
            @file_put_contents($file, $yaml);

        }
        return $this->app['render']->render('@ConfigEditor/base.twig', array(
            'form' => $form->createView()
        ));
    }

}