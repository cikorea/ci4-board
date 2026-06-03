<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use OpenApi\Generator;

/**
 * OpenAPI 스펙 생성 명령어
 *
 * 사용법:
 *   php spark swagger:generate          — PHP 애노테이션에서 자동 생성
 *   php spark swagger:generate --yaml   — YAML 형식으로 출력
 *   php spark swagger:generate --validate — 현재 YAML 스펙 유효성 검사
 */
class SwaggerGenerate extends BaseCommand
{
    protected $group       = 'Swagger';
    protected $name        = 'swagger:generate';
    protected $description = 'OpenAPI 3.0 스펙을 생성하거나 검사합니다.';
    protected $usage       = 'swagger:generate [options]';
    protected $options     = [
        '--yaml'     => 'YAML 형식으로 출력 (기본: JSON)',
        '--validate' => '현재 openapi.yaml 파일 유효성 검사만 수행',
        '--output'   => '출력 경로 (기본: public/docs/openapi.yaml)',
    ];

    private const SCAN_PATHS = [
        APPPATH . 'Controllers/Api',
        APPPATH . 'Models',
    ];

    public function run(array $params): void
    {
        $output   = CLI::getOption('output') ?? FCPATH . 'docs/openapi.yaml';
        $validate = CLI::getOption('validate') !== null;
        $yaml     = CLI::getOption('yaml') !== null || str_ends_with($output, '.yaml') || str_ends_with($output, '.yml');

        if ($validate) {
            $this->validate($output);
            return;
        }

        $this->generate($output, $yaml);
    }

    private function generate(string $output, bool $yaml): void
    {
        CLI::write('Scanning: ' . implode(', ', array_map('basename', self::SCAN_PATHS)), 'yellow');

        $openapi = (new Generator())->generate(self::SCAN_PATHS);

        if ($yaml) {
            $content = $openapi->toYaml();
        } else {
            $content = $openapi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $dir = dirname($output);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($output, $content);

        CLI::write('Generated: ' . realpath($output), 'green');
        CLI::write('Endpoints: ' . count($openapi->paths ?? []), 'cyan');
    }

    private function validate(string $yamlPath): void
    {
        if (! is_file($yamlPath)) {
            CLI::error("File not found: {$yamlPath}");
            return;
        }

        CLI::write("Validating: {$yamlPath}", 'yellow');

        try {
            $content = file_get_contents($yamlPath);
            $spec    = \Symfony\Component\Yaml\Yaml::parse($content);
            $this->checkRequiredFields($spec);
            CLI::write('OK: openapi.yaml is valid.', 'green');
        } catch (\Exception $e) {
            CLI::error('Validation error: ' . $e->getMessage());
        }
    }

    private function checkRequiredFields(array $spec): void
    {
        $required = ['openapi', 'info', 'paths'];
        foreach ($required as $field) {
            if (! isset($spec[$field])) {
                throw new \RuntimeException("Missing required field: '{$field}'");
            }
        }

        $pathCount = count($spec['paths'] ?? []);
        CLI::write("  openapi: {$spec['openapi']}", 'cyan');
        CLI::write("  title:   {$spec['info']['title']}", 'cyan');
        CLI::write("  paths:   {$pathCount}", 'cyan');
    }
}
