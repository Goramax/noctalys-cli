<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Goramax\NoctalysCli\Utils\GistConfigLoader;

class Template implements StepInterface
{
    public function run(array &$context): bool
    {
        $helper = $context['helper'];
        $input = $context['input'];
        $output = $context['output'];

        // Récupération des engines via l'utilitaire
        $validTemplates = [];
        $engines = GistConfigLoader::getEngines();
        foreach ($engines as $engine) {
            if (isset($engine['name'])) {
                $validTemplates[] = $engine['name'];
            }
        }
        if (empty($validTemplates)) {
            $output->writeln('<error>Could not fetch template engines from gist. Using default engines: latte, smarty, twig.</error>');
            $validTemplates = ['latte', 'smarty', 'twig'];
        }
        array_unshift($validTemplates, 'None');
        
        // Check if template is provided as option
        $template = $input->getOption('template-engine');
        
        if (!$template) {
            // Ask user to choose template
            $question = new ChoiceQuestion(
                'Choose a template engine',
                $validTemplates,
                0
            );
            $template = $helper->ask($input, $output, $question);
        } else {
            // Validate provided template option
            if (!in_array($template, $validTemplates)) {
                $output->writeln("<error>Invalid template '$template'. Valid options are: " . implode(', ', $validTemplates) . "</error>");
                return false;
            }
        }

        $context['template'] = $template;
        $output->writeln("Template engine selected: <info>$template</info>");
        
        // Récupération des CSS frameworks via l'utilitaire
        $validCss = [];
        $cssFrameworks = GistConfigLoader::getCss();
        foreach ($cssFrameworks as $css) {
            if (isset($css['name'])) {
                $validCss[] = $css['name'];
            }
        }
        if (empty($validCss)) {
            $output->writeln('<error>Could not fetch CSS frameworks from gist. Using default: None</error>');
            $validCss = ['None'];
        } else {
            array_unshift($validCss, 'None');
        }

        // Ask user to choose CSS framework
        $css = $input->getOption('css-framework');
        if (!$css) {
            $question = new ChoiceQuestion(
                'Choose a CSS framework',
                $validCss,
                0
            );
            $css = $helper->ask($input, $output, $question);
        } else {
            // Validation insensible à la casse
            $cssLower = strtolower($css);
            $validCssLower = array_map('strtolower', $validCss);
            if (!in_array($cssLower, $validCssLower)) {
                $output->writeln("<error>Invalid CSS framework '$css'. Valid options are: " . implode(', ', $validCss) . "</error>");
                return false;
            }
            // Remet la casse d'origine pour le contexte
            $css = $validCss[array_search($cssLower, $validCssLower)];
        }
        $context['css'] = $css;
        $output->writeln("CSS framework selected: <info>$css</info>");
        
        return true;
    }
}