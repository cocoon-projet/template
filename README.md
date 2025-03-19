# Cocoon Template Engine

Un moteur de template PHP simple et puissant, inspiré de Twig et Blade.

## Installation

```bash
composer require cocoon-projet/template
```

## Configuration

```php
use Cocoon\View\Twide;

Twide::init([
    'views' => 'resources/views',
    'cache' => 'temp/views',
    'extension' => '.tpl.php'
]);
```

## Extensions Disponibles

### Extensions par défaut
- **TextExtension** : Manipulation de texte
- **DateExtension** : Gestion des dates
- **ArrayExtension** : Manipulation des tableaux

## Syntaxe de Base

### Variables
```php
{{ variable }}
{{ user.name }}
{{ numbers[0] }}
```

### Conditions
```php
@if(condition)
    Contenu
@elseif(autre_condition)
    Contenu alternatif
@else
    Contenu par défaut
@endif
```

### Boucles
```php
@foreach(items as item)
    {{ item.name }}
@endforeach

@for(i = 0; i < 10; i++)
    {{ i }}
@endfor
```

### Layouts et Sections
```php
@layout('layout')
    @section('content')
        Contenu de la page
    @endsection
@endlayout
```

## Extensions

### TextExtension
```php
{{ 'texte'|excerpt(50) }}
{{ 'titre'|slug }}
{{ 'texte'|wordcount }}
{{ 'texte'|str_starts_with('prefix') }}
```

### DateExtension
```php
{{ date|timeago }}
{{ date|age }}
{{ date|calendar }}
{{ date|duration }}
{{ is_future(date) }}
{{ is_past(date) }}
```

### ArrayExtension
```php
{{ array|sort('name') }}
{{ array|filter('status', 'active') }}
{{ array|map('id') }}
{{ array|unique }}
{{ array|first }}
{{ array|last }}
```

## Licence

MIT
