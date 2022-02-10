<?php

namespace Cocoon\View\Compiler;

use ReflectionProperty;
use Cocoon\View\Template;
use InvalidArgumentException;
use Cocoon\View\TemplateException;
use Cocoon\View\Compiler\Features\CompileEchos;
use Cocoon\View\Compiler\Features\CompileLoops;
use Cocoon\View\Compiler\Features\CompileOther;
use Cocoon\View\Compiler\Features\CompileIncludes;
use Cocoon\View\Compiler\Features\CompileConditionals;
use Cocoon\View\Compiler\Features\CompileLayoutsAndStacks;

/**
 * Parseur du template Twide
 *
 * Class Parser
 */
class Parser
{
    /**
     * @var array // comments tag
     */
    protected $comment = ['{*', '*}'];
    /**
     * @var array // remplace comments tag
     */
    protected $replace = ['<!--', '-->'];
    /**
     * @var array // javascript code
     */
    protected $javascripts = [];
    /**
     * Liste des nouvelles directives
     *
     * @var array
     */
    protected $custumDirectives = [];
    /**
     * Raccourci pour fonction php
     *
     * @var array
     */
    protected $filters = [];
    /**
     * Fonctions crées
     *
     * @var array
     */
    protected $functions = [];
    /**
     * String ou boleean expression
     *
     * @var array
     */
    protected $reservedWords = [
        'true', 'false', 'null'
    ];
    /**
     * Donnée envoyée au template
     *
     * @var array
     */
    protected $data;

    use
        CompileEchos,
        CompileConditionals,
        CompileIncludes,
        CompileLayoutsAndStacks,
        CompileLoops,
        CompileOther;

    /**
     * Parsing et complilation des templates
     *
     * @param Template $template
     */
    public function __construct(Template $template)
    {
        $this->custumDirectives = $template->getCustumDirectives();
        $this->filters = $template->getFunction()->getFilters();
        $this->functions = $template->getFunction()->getFunctions();
        $this->data = $template->getData();
        // TODO: $this->data['__default__'] = null; mettre dans template
    }

    /**
     * Utiliser pour les tests unitaires.
     *
     * @param array $data
     */
    public function setData($data = [])
    {
        $this->data = $data;
    }
    /**
     * Parse les templates .tpl.php
     *
     * @param string $code
     * @return mixed
     */
    public function parse($code)
    {
        $content = str_replace(array("\r\n", "\r"), "\n", $code);
        // Remplace les tags des commentaires
        $content = str_replace($this->comment, $this->replace, $content);
        preg_match_all('!@script(.*?)@endscript!s', $content, $matches);
        $this->javascripts = $matches[1];
        $content = preg_replace("!@script(.*?)@endscript!s", '@javascript(code)', $content);
        $result = preg_replace_callback(
            '#(@?{{{?\s?([^\t\r\n}]+)\s?}?}})|(@[a-z]+\s?((\([^\t\r\n}]+)\))?)#s',
            [$this, 'callback'],
            $content
        );
        return $result;
    }

    /**
     * Renvoie les fonctions permettants de compiler le template
     *
     * @param string $matches
     * @return string
     */
    protected function callback($matches)
    {
        list($tag) = $matches;
        if (preg_match('/(@?[{]+)\s*(.*?)\s*([}]+)$/', $tag, $code)) {
            return $this->parseEchoTags($code);
        } elseif (preg_match('/@([a-z]+)\s*(\((.*)\))?/', $tag, $code)) {
            return $this->parseArobaseTags($code);
        }
    }
    /**
     * Parse les tags de type string
     *
     * @param array $code
     * @return string
     */
    protected function parseEchoTags($code)
    {
        if ($code[1] == '@{{' && $code[3] == '}}') {
            return '{{ ' . $code[2] . ' }}';
        }
        if ($code[1] == '{{' && $code[3] == '}}') {
            return $this->compileEcho($code[2]);
        } elseif ($code[1] == '{{{' && $code[3] == '}}}') {
            return $this->compileNoEscape($code[2]);
        } else {
            throw new InvalidArgumentException('Balise non valide, vous devez utiliser {{ $var }} ou {{{ $var }}}');
        }
    }
    /**
     * Parse les tags de type arobase @extends
     *
     * @param array $code
     * @return void
     */
    protected function parseArobaseTags($code)
    {
        $pattern = count($code);
        $compileFind = 'compile' . ucfirst($code[1]);
        $arg = $code[3] ?? null;
        if ($compileFind == 'compileJavascript') {
            $arg = $this->javascripts;
        }
        if (method_exists($this, $compileFind)) {
            if ($pattern == 2) {
                return $this->$compileFind();
            }
            return $this->$compileFind($arg);
        } elseif (isset($this->custumDirectives['@' . $code[1]])) {
            $custum = $this->custumDirectives['@' . $code[1]];
            // TODO: faire une function pour traiter les arguments des directives
            // prend actuellemnt qu'un paramètre...
            if ($pattern == 2) {
                return $custum();
            } else {
                return $custum($this->resolveExpression($arg));
            }
        } else {
            throw new InvalidArgumentException('Le tag @' . $code[1] . ' n\'éxiste pas');
        }
    }
    /**
     * Traitement string|array|object|numeric|bolean|null expression echo
     *
     * @param string $code
     * @return string
     */
    protected function resolveExpression($code)
    {
        // bolean ou null expression
        if (in_array($code, $this->reservedWords)) {
            return $code;
        }
        // numeric et math expression
        if (preg_match('/\-?[0-9]+(?:\.[0-9]+)?/A', $code)) {
            return $code;
        }
        // variable array expression
        if (preg_match('/([a-zA-Z0-9_]+)(\[)(.*?)(\])/', $code)) {
            return '$' . $code;
        }
        // array ou object avec les expressions comprenant un point {{ person.name }}
        if (strpos($code, '.')) {
            return $this->expressionDataType($code);
        }
        // string ou array
        if (preg_match('/(\'|\[|")(.*?)(\'|\]|")/', $code)) {
            return $code;
        }
        return '$' . $code;
    }
    /**
     * Parsing des expressions et filtres des variables et arguments {{ name|substr:0,1}}
     *
     * @param string $code
     * @return string
     */
    protected function expressionFilters($code)
    {
        if (!strpos($code, '|')) {
            return $this->resolveExpression($code);
        }
        $filters = explode('|', $code);
        $var = $this->resolveExpression($filters[0]);
        $filters[0] = '';
        array_shift($filters);
        $functionExists = function ($function) {
            if (isset($this->filters[$function])) {
                if (is_string($this->filters[$function])) {
                    return $this->filters[$function];
                }
                return '$this->filter';
            } else {
                throw new TemplateException('Le filtre ' . $function . ' n\éxiste pas');
            }
        };

        $resolveArguments = function ($arguments) {
            $args = explode(', ', $arguments);
            $return = [];
            foreach ($args as $arg) {
                $return[] = $this->resolveExpression(trim($arg));
            }
            return implode(', ', $return);
        };
        $expression = '';

        foreach ($filters as $key => $filter) {
            if (preg_match('/([a-zA-Z]+)\s*\((.*)\)/', $filter, $matches)) {
                $filter = $matches;
            } else {
                $filter = [$filters[0], $filters[0]];
            }
            $separateur = ',';

            if (!is_string($this->filters[$filter[1]])) {
                $arg = isset($filter[2]) ? ', ' . $filter[2] : '';
                $filter[2] = '\'' . $filter[1] . '\', ' . $var . $arg;
                $var = '';
                $separateur = '';
            };
            $expression = sprintf(
                '%s(%s%s)',
                $functionExists($filter[1]),
                $key === 0 ? trim($var) : $expression,
                isset($filter[2]) ? "{$separateur}{$resolveArguments($filter[2])}" : ''
            );
        }
        return $expression;
    }
    /**
     * Retourne le résultat d'une fonction appelée dans une balise echo {{ :url:'article/add' }}
     *
     * @param string $code
     * @return string
     */
    protected function expressionFunctions($code)
    {
        return $this->parseArguments($code[1], $code[2], '$this->func');
        // TODO: voir si l'on retourne une fonction sans paramètre
        //return '$this->' . $function . '()';
    }
    /**
     * Traitement des arguments des functions appelées {{ asset('path/to/css') }}
     *
     * @param string $filters
     * @param string $var
     * @return string
     */
    protected function parseArguments($function, $filters, $var)
    {
        $resolveArguments = function ($filters) {
            $args = explode(',', $filters);
            $return = [];
            foreach ($args as $arg) {
                $return[] = $this->resolveExpression(trim($arg));
            }
            return implode(', ', $return);
        };

        return sprintf(
            '%s(\'' . $function . '\', %s)',
            $var,
            $resolveArguments($filters)
        );
    }
    /**
     * Traitement des conditions if
     *
     * @param string $code
     * @return string
     */
    public function parseConditionnals($code)
    {
        $keys = preg_split("/(and|or)/", $code, 0, PREG_SPLIT_DELIM_CAPTURE);

        $if = function ($value) {
            if (preg_match('/([^\t\r\n}]+)\s?(==|!=|<|>|>=|<=|===|!==})\s?([^\t\r\n}]+)/', $value, $matches)) {
                return trim($this->expressionFilters($matches[1])) . ' ' . $matches[2] .
                    ' ' . trim($this->expressionFilters($matches[3]));
            } else {
                return $this->expressionFilters($value);
            }
        };

        $expression = '';

        foreach ($keys as $value) {
            if ($value === 'and' or $value === 'or') {
                $expression .= $value . ' ';
            } else {
                $expression .= $if(trim($value)) . ' ';
            }
        }
        return $expression;
    }
    /**
     * Determine le typage des donnée a afficher object ou array ou string
     *
     * @param string $data
     * @return string|null
     */
    public function expressionDataType($data) :string
    {
        $resolveArguments = function ($arguments) {
            $args = explode(', ', $arguments);
            $return = [];
            foreach ($args as $arg) {
                $return[] = $this->resolveExpression(trim($arg));
            }
            return implode(', ', $return);
        };

        if (strpos($data, '.')) {
            $parse = explode('.', $data);
            $expr = array_shift($parse);
            $return = '$' . $expr;
            if (is_object($this->data[$expr])) {
                foreach ($parse as $value) {
                    if (property_exists($this->data[$expr], $value) &&
                        $this->propertyIsPublic($this->data[$expr], $value)) {
                        $return .= '->' . $value;
                        // TODO: methode static a gerer
                    } elseif (method_exists($this->data[$expr], $value)) {
                        $return .= '->' . $value . '()';
                    } elseif (preg_match('/([a-zA-Z]+)\s*\((.*)\)/', $value, $matches)) {
                        return $return . '->' . $matches[1] . '(' . $resolveArguments($matches[2]) . ')';
                    } elseif (method_exists($this->data[$expr], 'get' . ucfirst($value))) {
                        $return .= '->get' . ucfirst($value) . '()';
                    }
                }
            } elseif (is_array($this->data[$expr])) {
                foreach ($parse as $value) {
                    $return .= '[\'' . $value . '\']';
                }
            }
        }
        return $return;
    }

    /**
     * Determine si la propriétée de l'object est public
     *
     * @param object $object
     * @param string $property
     * @return bool
     * @throws \ReflectionException
     */
    private function propertyIsPublic($object, $property)
    {
        return (new ReflectionProperty($object, $property))->isPublic();
    }
}
