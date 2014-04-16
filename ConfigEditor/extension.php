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
    public $config;

    /**
     * @return array
     */
    public function info()
    {

        return array(
            'name' => "ConfigEditor",
            'description' => "a visual config editor",
            'tags' => array('config', 'editor', 'admin', 'tool'),
            'type' => "Administrative Tool",
            'author' => "Andrey Pitko",
            'link' => "http://artvisio.com",
            'email' => 'andrey.p@artvisio.com',
            'version' => "0.3",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "1.4.3",
            'first_releasedate' => "2014-04-01",
            'latest_releasedate' => "2014-04-16"
        );

    }

    public function initialize()
    {
        $this->config = $this->getConfig();
        $this->app->match($this->config['path'], array($this, 'configEditor'));
        $this->addMenuOption(__('ConfigEditor'), $this->config['path'], "icon-list");
        $this->addTwigFunction('getParameter', 'getParameter');
    }

    public function getParameter($key){
        if ( !isset ($this->config['parameters'][$key]))
            throw new \Exception("Wrong parameter");
        if ( is_array($this->config['parameters'][$key]))
            return $this->config['parameters'][$key]['value'];
        return $this->config['parameters'][$key];
    }

    public function configEditor()
    {
        $currentUser = $this->app['users']->getCurrentUser();
        $currentUserId = $currentUser['id'];
        $this->authorized = false;
        if (!isset($this->config['permissions']) || !is_array($this->config['permissions'])) {
            $this->config['permissions'] = array('root', 'admin', 'developer');
        } else {
            $this->config['permissions'][] = 'root';
        }
        foreach ($this->config['permissions'] as $role) {
            if ($this->app['users']->hasRole($currentUserId, $role)) {
                $this->authorized = true;
                break;
            }
        }
        if (!$this->authorized) {
            return new Response();
        }
        $file = __DIR__ . '/config.yml';
        if (@!is_readable($file) || !@is_writable($file)) {
            throw new \Exception(
                __(
                    "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                    array('%s' => $file)
                ));
        }
        $this->app['twig.loader.filesystem']->addPath(BOLT_PROJECT_ROOT_DIR . "/app/view");
        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/views/', 'ConfigEditor');
        $fb = $this->app['form.factory']->createBuilder('form');
        foreach ($this->config['parameters'] as $key => $value) {
            if (!is_array($value))
                    {
                        $value = array('value' => $value);
                    }
            $value = array_merge(array('type' => 'text', 'label' => $key, 'required' => true), $value);
            $fb->add(
                $key,
                $value['type'],
                array('required' => $value['required'], 'data' => $value['value'], 'label' => $value['label'])
            );
        }
        $fb->add('submit', 'submit');
        $form = $fb->getForm();
        if ($this->app['request']->isMethod('POST')) {
            $parser = new YamlParser();
            $data = $parser->parse(@file_get_contents($file));
            $sdata = &$data['parameters'];
            $form->bind($this->app['request']);
            foreach ($this->app['request']->get('form') as $key => $value) {
                if ($key === "submit" || $key === "_token")
                    continue;
                if (is_array($sdata[$key])) {
                    $sdata[$key]['value'] = $value;
                } else {
                    $sdata[$key] = $value;
                }
            }
            $dumper = new YamlDumper();
            $dumper->setIndentation(2);
            $yaml = $dumper->dump($data, 9999);
            @file_put_contents($file, $yaml);

        }

        return $this->app['render']->render(
            '@ConfigEditor/base.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

}