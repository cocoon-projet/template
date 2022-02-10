<?php

namespace Cocoon\View;

use DateTimeZone;
use Carbon\Carbon;
use Cocoon\View\TemplateException;

/**
 * gestion des fonctions ajoutées pour les templates
 */
class FunctionsTemplate
{
    /**
     * Liste des fonctions
     *
     * @var array
     */
    private $functions = [];
    /**
     * Raccourci pour fonction php
     *
     * @var array
     */
    protected $filters = array('lower' => 'strtolower',
        'notags' => 'strip_tags',
        'capitalize' => 'ucfirst',
        'title' => 'ucwords',
        'upper' => 'strtoupper',
        'escurl' => 'rawurlencode',
        'chunk' => 'array_chunk',
        'slice' => 'array_slice',
        'merge' => 'array_merge',
        );
    public function __construct()
    {
        $this->coreFilters();
    }
    /**
     * Ajoute un Filter
     *
     * @param string $name
     * @param string|callable $callback
     * @return void
     */
    public function addFilter($name, $callback)
    {
        if ($this->existsFilter($name)) {
            throw new TemplateException(
                'la fonction "' . $name . '" est déjà enregistrée pour le template.'
            );
        }
        $this->filters[$name] = $callback;
    }
    /**
     * Retourne un filtre
     *
     * @param string $name Nom de la fonction
     * @return void
     */
    public function getFilter($name, ...$args)
    {
        if (!$this->existsFilter($name)) {
            throw new TemplateException('La fonction "' . $name . '" n\'éxiste pas pour le template.');
        }
        return $this->filters[$name](...$args);
    }
    /**
     * Vérifie si la function existe
     *
     * @param string $name Nom de la fonction
     * @return bool
     */
    public function existsFilter($name)
    {
        return isset($this->filters[$name]);
    }
    /**
     * Retourne les filtres
     *
     * @return array
     */
    public function getFilters() :array
    {
        return $this->filters;
    }
       /**
     * Ajoute une function
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function addFunction($name, callable $callback)
    {
        if ($this->existsFunction($name)) {
            throw new TemplateException(
                'la fonction "' . $name . '" est déjà enregistrée pour le template.'
            );
        }
        $this->functions[$name] = $callback;
    }
    /**
     * Retourne une fonction
     *
     * @param string $name Nom de la fonction
     * @return void
     */
    public function getFunction($name, ...$args)
    {
        if (!$this->existsFunction($name)) {
            throw new TemplateException('La fonction "' . $name . '" n\'éxiste pas pour le template.');
        }
        return $this->functions[$name](...$args);
    }
    /**
     * Vérifie si la function existe
     *
     * @param string $name Nom de la fonction
     * @return bool
     */
    public function existsFunction($name)
    {
        return isset($this->functions[$name]);
    }
    /**
     * Retourne les fonctions crées
     *
     * @return array
     */
    public function getFunctions() :array
    {
        return $this->functions;
    }

    private function coreFilters()
    {
        $this->addFilter('lenght', function ($value) {
            if (is_string($value)) {
                return strlen($value);
            } elseif (is_array($value)) {
                return count($value);
            }
        });
        $this->addFilter('date', function ($value, $format = null) {
            Carbon::setLocale('fr');
            $date = new Carbon($value);
            $date->timezone = new DateTimeZone("Europe/Paris");
            if ($value === 'now' && $format == null) {
                return $date;
            }
            return $date->format($format);
        });
        $this->addFilter('diffForHumans', function ($value) {
            Carbon::setLocale('fr');
            $date = new Carbon($value);
            $date->timezone = new DateTimeZone("Europe/Paris");
            return $date->diffForHumans();
        });
    }
}
