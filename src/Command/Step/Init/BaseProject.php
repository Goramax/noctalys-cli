<?php

namespace Noctalys\Cli\Command\Step\Init;
use Noctalys\Cli\Command\Step\StepInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class BaseProject implements StepInterface{
    public function run(array &$context): bool
    {
        $helper = $context['helper'];
        $input = $context['input'];
        $output = $context['output'];

        // Check if project type is Frontend or Mixed
        if (!isset($context['type']) || ($context['type'] !== 'Frontend' && $context['type'] !== 'Mixed')) {
            // Skip base project selection for non-frontend projects
            $context['base_project'] = 'Minimal';
            return true;
        }

        // Define valid base projects with descriptions
        $validProjects = ['Minimal', 'Complete'];
        $projectDescriptions = [
            'Minimal' => 'Just a homepage to start your project',
            'Complete' => 'A mini multi-page site showcasing different features of the framework'
        ];

        // Check if base project is provided as option
        $baseProject = $input->getOption('base-project');

        if (!$baseProject) {
            // Ask user to choose base project with descriptions
            $question = new ChoiceQuestion(
                'Choose a base project',
                array_map(function($project) use ($projectDescriptions) {
                    return $project . ' - ' . $projectDescriptions[$project];
                }, $validProjects),
                0
            );
            $response = $helper->ask($input, $output, $question);
            // Extract just the project name from the response
            $baseProject = substr($response, 0, strpos($response, ' - '));
        } else {
            // Validate provided base project option
            if (!in_array($baseProject, $validProjects)) {
                $output->writeln("<error>Invalid base project '$baseProject'. Valid options are: " . implode(', ', $validProjects) . "</error>");
                return false;
            }
        }

        $context['base_project'] = $baseProject;
        $output->writeln("Base project selected: <info>$baseProject</info>");
        $output->writeln("<comment>" . $projectDescriptions[$baseProject] . "</comment>");

        return true;
    }
}