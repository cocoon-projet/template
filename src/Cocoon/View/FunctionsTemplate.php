<?php

namespace Cocoon\View;

use DateTimeZone;
use Carbon\Carbon;
use Cocoon\View\TemplateException;

/**
 * Gestionnaire des fonctions et filtres pour les templates
 *
 * Cette classe gère :
 * 1. Les fonctions personnalisées pour les templates
 * 2. Les filtres de transformation (ex: upper, lower, date)
 * 3. Les raccourcis vers les fonctions PHP natives
 *
 * Exemples d'utilisation dans les templates :
 * ```
 * {{ name|upper }}                    // Filtre simple
 * {{ date|date('Y-m-d') }}           // Filtre avec arguments
 * {{ users|length }}                  // Filtre de comptage
 * {{ created_at|diffForHumans }}      // Filtre de date relative
 * ```
 *
 * Filtres disponibles par défaut :
 * - lower : Convertit en minuscules
 * - upper : Convertit en majuscules
 * - title : Capitalise chaque mot
 * - notags : Supprime les balises HTML
 * - length : Compte les caractères ou éléments
 * - date : Formate une date
 * - diffForHumans : Date relative (il y a X temps)
 *
 * @package Cocoon\View
 */
class FunctionsTemplate
{
    /**
     * Liste des fonctions personnalisées
     *
     * @var array<string, callable>
     */
    private array $functions = [];

    /**
     * Liste des filtres de base
     *
     * @var array<string, callable|string>
     */
    protected array $filters = [
        'lower' => 'strtolower',
        'upper' => 'strtoupper',
        'title' => 'ucwords',
        'capitalize' => 'ucfirst',
        'notags' => 'strip_tags',
        'escurl' => 'rawurlencode',
        'chunk' => 'array_chunk',
        'slice' => 'array_slice',
        'merge' => 'array_merge',
        'join' => 'implode',
        'trim' => 'trim',
        'nl2br' => 'nl2br',
        'round' => 'round',
        'floor' => 'floor',
        'ceil' => 'ceil',
    ];

    /**
     * Formats de date prédéfinis
     *
     * @var array<string, string>
     */
    protected array $dateFormats = [
        'short' => 'd/m/Y',
        'medium' => 'd M Y',
        'long' => 'd F Y à H:i',
        'full' => 'l d F Y à H:i:s',
        'time' => 'H:i',
        'time_full' => 'H:i:s',
        'mysql' => 'Y-m-d H:i:s',
        'rss' => 'D, d M Y H:i:s O',
    ];

    /**
     * Initialise le gestionnaire avec les filtres de base
     */
    public function __construct()
    {
        $this->registerCoreFilters();
    }

    /**
     * Ajoute un nouveau filtre
     *
     * @param string $name Nom du filtre
     * @param callable|string $callback Fonction de transformation
     * @throws TemplateException Si le filtre existe déjà
     */
    public function addFilter(string $name, callable|string $callback): void
    {
        if ($this->existsFilter($name)) {
            throw new TemplateException(
                sprintf('Le filtre "%s" est déjà enregistré', $name)
            );
        }
        $this->filters[$name] = $callback;
    }

    /**
     * Applique un filtre sur une valeur
     *
     * @param string $name Nom du filtre
     * @param mixed ...$args Valeur à filtrer et arguments additionnels
     * @return mixed Valeur transformée
     * @throws TemplateException Si le filtre n'existe pas
     */
    public function getFilter(string $name, mixed ...$args): mixed
    {
        if (!$this->existsFilter($name)) {
            throw new TemplateException(
                sprintf('Le filtre "%s" n\'existe pas', $name)
            );
        }

        $callback = $this->filters[$name];
        return is_callable($callback) ? $callback(...$args) : $callback(...$args);
    }

    /**
     * Vérifie si un filtre existe
     *
     * @param string $name Nom du filtre
     * @return bool true si le filtre existe
     */
    public function existsFilter(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    /**
     * Retourne la liste des filtres disponibles
     *
     * @return array<string, callable|string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Ajoute une fonction personnalisée
     *
     * @param string $name Nom de la fonction
     * @param callable $callback Fonction à exécuter
     * @throws TemplateException Si la fonction existe déjà
     */
    public function addFunction(string $name, callable $callback): void
    {
        if ($this->existsFunction($name)) {
            throw new TemplateException(
                sprintf('La fonction "%s" est déjà enregistrée', $name)
            );
        }
        $this->functions[$name] = $callback;
    }

    /**
     * Exécute une fonction personnalisée
     *
     * @param string $name Nom de la fonction
     * @param mixed ...$args Arguments de la fonction
     * @return mixed Résultat de la fonction
     * @throws TemplateException Si la fonction n'existe pas
     */
    public function getFunction(string $name, mixed ...$args): mixed
    {
        if (!$this->existsFunction($name)) {
            throw new TemplateException(
                sprintf('La fonction "%s" n\'existe pas', $name)
            );
        }
        return $this->functions[$name](...$args);
    }

    /**
     * Vérifie si une fonction existe
     *
     * @param string $name Nom de la fonction
     * @return bool true si la fonction existe
     */
    public function existsFunction(string $name): bool
    {
        return isset($this->functions[$name]);
    }

    /**
     * Retourne la liste des fonctions disponibles
     *
     * @return array<string, callable>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Enregistre les filtres de base
     */
    private function registerCoreFilters(): void
    {
        // Filtre de longueur amélioré
        $this->addFilter('length', function (mixed $value): int {
            if (is_string($value)) {
                return mb_strlen($value);
            }
            if (is_array($value)) {
                return count($value);
            }
            if (is_object($value) && method_exists($value, 'count')) {
                return $value->count();
            }
            throw new TemplateException(
                'Le filtre length ne peut être utilisé que sur une chaîne, un tableau ou un objet comptable'
            );
        });

        // Filtre de date amélioré
        $this->addFilter('date', function (mixed $value, ?string $format = null): string {
            try {
                Carbon::setLocale('fr');
                $date = new Carbon($value);
                $date->timezone = new DateTimeZone("Europe/Paris");

                // Si le format est un format prédéfini
                if ($format !== null && isset($this->dateFormats[$format])) {
                    $format = $this->dateFormats[$format];
                }

                // Format par défaut selon le contexte
                if ($format === null) {
                    if ($value === 'now') {
                        return $date->format($this->dateFormats['long']);
                    }
                    return $date->format($this->dateFormats['short']);
                }

                return $date->format($format);
            } catch (\Exception $e) {
                throw new TemplateException(
                    sprintf('Erreur lors du formatage de la date : %s', $e->getMessage())
                );
            }
        });

        // Filtre de date relative amélioré
        $this->addFilter('diffForHumans', function (mixed $value, ?string $other = null): string {
            try {
                Carbon::setLocale('fr');
                $date = new Carbon($value);
                $date->timezone = new DateTimeZone("Europe/Paris");

                if ($other !== null) {
                    $otherDate = new Carbon($other);
                    return $date->diffForHumans($otherDate);
                }

                return $date->diffForHumans();
            } catch (\Exception $e) {
                throw new TemplateException(
                    sprintf('Erreur lors du calcul de la différence de dates : %s', $e->getMessage())
                );
            }
        });

        // Nouveaux filtres pour les dates
        $this->addFilter('age', function (mixed $value): int {
            try {
                $date = new Carbon($value);
                return $date->age;
            } catch (\Exception $e) {
                throw new TemplateException(
                    sprintf('Erreur lors du calcul de l\'âge : %s', $e->getMessage())
                );
            }
        });

        $this->addFilter('timeago', function (mixed $value): string {
            try {
                Carbon::setLocale('fr');
                $date = new Carbon($value);
                return $date->diffForHumans(['parts' => 2, 'join' => true]);
            } catch (\Exception $e) {
                throw new TemplateException(
                    sprintf('Erreur lors du calcul du temps écoulé : %s', $e->getMessage())
                );
            }
        });
    }
}
