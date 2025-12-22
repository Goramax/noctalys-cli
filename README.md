# Noctalys CLI

> [!NOTE]
> This installer and framework are in early development and may be unstable and subject to change.

A command-line installer and scaffolding tool for Noctalys-based projects.

## Installation

### Global (recommended)
Install globally so you can run `noctalys` from anywhere:

```bash
composer global require noctalys/cli
```

### Local (per project)
Add the CLI as a dev dependency in a project:

```bash
composer require --dev noctalys/cli
```

Run via the project's vendor bin:

```bash
vendor/bin/noctalys --help
```

## Usage

> [!WARNING]
> Template engine syntax converter (Latte, Twig, Smarty) is still WIP, you may need to adjust templates manually after generation.

Common commands:

```bash
# Initialize a new Noctalys project in current directory
noctalys init

# Generate a page
noctalys make:page Home

# Generate a layout
noctalys make:layout Main
```

If installed locally:

```bash
vendor/bin/noctalys init
vendor/bin/noctalys make:page Home
vendor/bin/noctalys make:layout Main
```

## Requirements
- PHP 8.1+ (recommended)
- Composer 2.x

## License
MIT
