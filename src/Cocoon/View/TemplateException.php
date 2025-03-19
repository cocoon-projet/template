<?php

namespace Cocoon\View;

use Exception;

/**
 * Exception spécifique aux erreurs de template
 *
 * Cette classe permet de gérer les erreurs spécifiques au système de templates,
 * comme les erreurs de syntaxe, les variables manquantes, les filtres invalides, etc.
 *
 * Exemples d'utilisation :
 * ```php
 * throw new TemplateException('Variable manquante : user');
 * throw new TemplateException('Filtre invalide : custom', ['template' => 'users/show.tpl']);
 * throw new TemplateException('Erreur de syntaxe', ['line' => 42, 'code' => '{{ invalid }}']);
 * ```
 *
 * @package Cocoon\View
 */
class TemplateException extends Exception
{
    /**
     * Informations contextuelles sur l'erreur
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Crée une nouvelle exception de template
     *
     * @param string $message Message d'erreur
     * @param array<string, mixed> $context Informations contextuelles
     * @param int $code Code d'erreur
     * @param Exception|null $previous Exception précédente
     */
    public function __construct(
        string $message,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Retourne les informations contextuelles de l'erreur
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Ajoute une information contextuelle
     *
     * @param string $key Clé du contexte
     * @param mixed $value Valeur associée
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Retourne une représentation textuelle de l'exception
     * incluant le contexte
     */
    public function __toString(): string
    {
        $string = parent::__toString();

        if (!empty($this->context)) {
            $string .= "\nContexte :\n";
            foreach ($this->context as $key => $value) {
                $string .= sprintf("- %s : %s\n", $key, print_r($value, true));
            }
        }

        return $string;
    }
}
