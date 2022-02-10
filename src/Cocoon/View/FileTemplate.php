<?php

namespace Cocoon\View;

class FileTemplate
{
    /**
     * Extension des templates à compiler
     *
     * @var string
     */
    protected $extention;
    /**
     * Répertoire des templates .tpl
     *
     * @var string
     */
    protected $pathTemplate;
    /**
     * Répertoire des templates compilés en php
     *
     * @var string
     */
    protected $pathTemplateCache;
    /**
     * @var string
     */
    private $ds = DIRECTORY_SEPARATOR;
    /**
     * Initilaise le Class FileTemplate
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->pathTemplate = $config['template_path'];
        $this->pathTemplateCache = $config['template_php_path'];
        $this->extention = $config['extention'];
    }
    /**
     * Chemin ver le fichier template .tpl
     *
     * @param string $file
     * @return string
     */
    public function getPathTemplate($file) :string
    {
        return $this->pathTemplate . $this->ds . $this->resolveDirectoryStructure($file);
    }
    /**
     * Chemin vers le fichier template php
     *
     * @param string $file
     * @return string
     */
    public function getPathTemplateCache($file) :string
    {
        return $this->pathTemplateCache . $this->ds . $file . '.php';
    }
    /**
     * Verifie si un template éxiste et est à jour
     *
     * @param string $file_template
     * @return boolean
     */
    public function existsAndIsExpired($file_template) :bool
    {
        if (file_exists($this->getPathTemplateCache($file_template)) &&
            filemtime($this->getPathTemplateCache($file_template)) >=
            filemtime($this->getPathTemplate($file_template))) {
            return true;
        }
        return false;
    }

    /**
     * Gestion des sous répertoires pour les fichiers template .tpl
     * Le point est le séparateur: ex   blog.index -> blog/index
     *
     * @param string $name nom du template
     * @return string
     */
    protected function resolveDirectoryStructure($name) :string
    {
        if (strpos($name, '.')) {
            return str_replace('.', $this->ds, $name) . $this->extention;
        } else {
            return $name . $this->extention;
        }
    }
    /**
     * Lecture du fichier template .tpl
     *
     * @param string $__template
     * @return string
     */
    public function read($__template) :string
    {
        $fp = fopen($this->getPathTemplate($__template), 'r');
        $content_file = fread($fp, filesize($this->getPathTemplate($__template)));
        fclose($fp);
        return $content_file;
    }

    /**
     * Ecriture du template php
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public function put($file, $content)
    {
        file_put_contents($this->getPathTemplateCache($file), $content);
    }
}
