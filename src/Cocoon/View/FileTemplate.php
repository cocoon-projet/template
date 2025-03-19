<?php

namespace Cocoon\View;

/**
 * Classe de gestion des fichiers templates
 *
 * Cette classe gère :
 * 1. La lecture des fichiers templates source (.tpl)
 * 2. La compilation et mise en cache des templates en PHP
 * 3. La vérification de la fraîcheur des fichiers compilés
 *
 * Configuration :
 * ```php
 * [
 *     'template_path' => '/path/to/templates',     // Dossier des templates source
 *     'template_php_path' => '/path/to/cache',     // Dossier des templates compilés
 *     'extension' => '.tpl'                        // Extension des fichiers template
 * ]
 * ```
 *
 * @package Cocoon\View
 */
class FileTemplate
{
    /**
     * Extension des fichiers templates
     *
     * @var string
     */
    protected string $extension;

    /**
     * Chemin vers le dossier des templates source
     *
     * @var string
     */
    protected string $pathTemplate;

    /**
     * Chemin vers le dossier des templates compilés
     *
     * @var string
     */
    protected string $pathTemplateCache;

    /**
     * Séparateur de dossiers selon le système
     *
     * @var string
     */
    private string $ds;

    /**
     * Initialise le gestionnaire de fichiers templates
     *
     * @param array{
     *     template_path: string,
     *     template_php_path: string,
     *     extension: string
     * } $config Configuration du gestionnaire
     * @throws TemplateException Si un paramètre requis est manquant
     */
    public function __construct(array $config)
    {
        if (!isset($config['template_path'])) {
            throw new TemplateException(
                'Le chemin vers les templates source (template_path) est requis'
            );
        }
        if (!isset($config['template_php_path'])) {
            throw new TemplateException(
                'Le chemin vers les templates compilés (template_php_path) est requis'
            );
        }
        if (!isset($config['extension'])) {
            throw new TemplateException(
                'L\'extension des fichiers template (extension) est requise'
            );
        }

        $this->pathTemplate = rtrim($config['template_path'], '/\\');
        $this->pathTemplateCache = rtrim($config['template_php_path'], '/\\');
        $this->extension = $config['extension'];
        $this->ds = DIRECTORY_SEPARATOR;

        $this->ensureDirectoriesExist();
    }

    /**
     * Vérifie et crée les dossiers nécessaires
     *
     * @throws TemplateException Si un dossier ne peut pas être créé
     */
    private function ensureDirectoriesExist(): void
    {
        foreach ([$this->pathTemplate, $this->pathTemplateCache] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw new TemplateException(
                    sprintf('Impossible de créer le dossier : %s', $dir)
                );
            }
        }
    }

    /**
     * Retourne le chemin complet vers un fichier template source
     *
     * @param string $file Identifiant du template (ex: "blog.index")
     * @return string Chemin complet vers le fichier
     */
    public function getPathTemplate(string $file): string
    {
        return $this->pathTemplate . $this->ds . $this->resolveDirectoryStructure($file);
    }

    /**
     * Retourne le chemin complet vers un fichier template compilé
     *
     * @param string $file Identifiant du template
     * @return string Chemin complet vers le fichier PHP
     */
    public function getPathTemplateCache(string $file): string
    {
        return $this->pathTemplateCache . $this->ds . $file . '.php';
    }

    /**
     * Vérifie si un template compilé existe et est à jour
     *
     * Un template est considéré périmé si :
     * - Le fichier compilé n'existe pas
     * - Le fichier source a été modifié après la compilation
     *
     * @param string $file_template Identifiant du template
     * @return bool true si le template est à jour
     * @throws TemplateException Si le fichier source n'existe pas
     */
    public function existsAndIsExpired(string $file_template): bool
    {
        $sourcePath = $this->getPathTemplate($file_template);
        $cachePath = $this->getPathTemplateCache($file_template);

        if (!file_exists($sourcePath)) {
            throw new TemplateException(
                sprintf('Le fichier template "%s" n\'existe pas', $sourcePath)
            );
        }

        return file_exists($cachePath) &&
               filemtime($cachePath) >= filemtime($sourcePath);
    }

    /**
     * Résout la structure de dossiers à partir d'un identifiant de template
     *
     * Convertit un identifiant avec points en chemin de fichier.
     * Exemple : "blog.index" -> "blog/index.tpl"
     *
     * @param string $name Identifiant du template
     * @return string Chemin relatif du fichier
     */
    protected function resolveDirectoryStructure(string $name): string
    {
        if (str_contains($name, '.')) {
            return str_replace('.', $this->ds, $name) . $this->extension;
        }
        
        return $name . $this->extension;
    }

    /**
     * Lit le contenu d'un fichier template
     *
     * @param string $template Identifiant du template
     * @return string Contenu du fichier
     * @throws TemplateException Si le fichier ne peut pas être lu
     */
    public function read(string $template): string
    {
        $path = $this->getPathTemplate($template);
        
        if (!file_exists($path)) {
            throw new TemplateException(
                sprintf('Le fichier template "%s" n\'existe pas', $path)
            );
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new TemplateException(
                sprintf('Impossible de lire le fichier template "%s"', $path)
            );
        }

        return $content;
    }

    /**
     * Écrit un template compilé dans le cache
     *
     * @param string $file Identifiant du template
     * @param string $content Contenu compilé
     * @throws TemplateException Si le fichier ne peut pas être écrit
     */
    public function put(string $file, string $content): void
    {
        $path = $this->getPathTemplateCache($file);
        
        if (file_put_contents($path, $content) === false) {
            throw new TemplateException(
                sprintf('Impossible d\'écrire le fichier compilé "%s"', $path)
            );
        }
    }
}
