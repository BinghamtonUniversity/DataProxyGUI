<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TestsController extends Controller
{
    /**
     * List all available test files
     */
    public function listTests(): JsonResponse
    {
        $testsPath = base_path('tests');
        $tests = [
            'unit' => [],
            'feature' => [],
        ];

        // Scan Unit tests (recursively)
        $unitPath = $testsPath . '/Unit';
        if (File::exists($unitPath)) {
            $unitFiles = File::glob($unitPath . '/**/*.php');
            foreach ($unitFiles as $file) {
                $relativePath = str_replace($unitPath . '/', '', $file);
                $tests['unit'][] = [
                    'name' => File::name($file),
                    'path' => str_replace(base_path() . '/', '', $file),
                    'directory' => dirname($relativePath) !== '.' ? dirname($relativePath) : null,
                ];
            }
        }

        // Scan Feature tests
        $featurePath = $testsPath . '/Feature';
        if (File::exists($featurePath)) {
            $featureFiles = File::glob($featurePath . '/**/*.php');
            foreach ($featureFiles as $file) {
                $relativePath = str_replace($featurePath . '/', '', $file);
                $tests['feature'][] = [
                    'name' => File::name($file),
                    'path' => str_replace(base_path() . '/', '', $file),
                    'directory' => dirname($relativePath) !== '.' ? dirname($relativePath) : null,
                ];
            }
        }

        return response()->json($tests);
    }

    /**
     * Run all tests
     */
    public function runAllTests(Request $request): JsonResponse
    {
        // Only allow in non-production environments
        if (app()->environment('production')) {
            return response()->json([
                'error' => 'Running tests is not allowed in production environment',
            ], 403);
        }

        try {
            $suite = $request->input('suite', 'all'); // 'all', 'unit', 'feature'
            
            $command = 'test';
            $options = [];
            
            if ($suite !== 'all') {
                $options['--testsuite'] = ucfirst($suite);
            }

            // Run tests and capture output
            Artisan::call($command, $options);
            $output = Artisan::output();

            // Parse output to extract results
            $results = $this->parseTestOutput($output);

            return response()->json([
                'success' => true,
                'output' => $output,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Error running tests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run a specific test file
     */
    public function runTestFile(Request $request): JsonResponse
    {
        // Only allow in non-production environments
        if (app()->environment('production')) {
            return response()->json([
                'error' => 'Running tests is not allowed in production environment',
            ], 403);
        }

        try {
            $file = $request->input('file');
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'error' => 'File path is required',
                ], 400);
            }

            // Remove base_path if it's included in the file path
            $file = str_replace(base_path() . '/', '', $file);
            $file = str_replace(base_path() . '\\', '', $file); // Handle Windows paths
            $file = str_replace('tests/', '', $file); // Remove tests/ prefix if present
            $file = str_replace('tests\\', '', $file); // Handle Windows paths
            
            // Ensure we're working with relative path from tests directory
            $testPath = base_path('tests/' . $file);
            
            if (!File::exists($testPath)) {
                return response()->json([
                    'success' => false,
                    'error' => "Test file not found: {$file}",
                ], 404);
            }

            // Use relative path from base_path for the test command
            // Convert backslashes to forward slashes for cross-platform compatibility
            $relativePath = 'tests/' . str_replace('\\', '/', $file);
            
            // Use shell_exec to avoid Windows permission issues with Process
            // Change to base directory first
            $originalDir = getcwd();
            chdir(base_path());
            
            try {
                $phpBinary = defined('PHP_BINARY') && PHP_BINARY ? escapeshellarg(PHP_BINARY) : 'php';
                $artisanPath = escapeshellarg('artisan');
                $testPath = escapeshellarg($relativePath);
                
                // Build command: php artisan test tests/Unit/Controllers/ApiControllerTest.php
                $command = "{$phpBinary} {$artisanPath} test {$testPath} 2>&1";
                
                // Execute command and capture output
                $output = shell_exec($command);
                
                // If output is empty, there might have been an error
                if ($output === null || trim($output) === '') {
                    $output = "Command executed but no output received. Command: {$command}";
                }
            } finally {
                // Restore original directory
                chdir($originalDir);
            }

            $results = $this->parseTestOutput($output);

            return response()->json([
                'success' => true,
                'output' => $output,
                'results' => $results,
                'file' => $file,
            ]);
        } catch (\Exception $e) {
            Log::error('Error running test file', [
                'file' => $request->input('file'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get test statistics
     */
    public function getTestStats(): JsonResponse
    {
        $testsPath = base_path('tests');
        
        $stats = [
            'total_tests' => 0,
            'unit_tests' => 0,
            'feature_tests' => 0,
            'test_files' => 0,
        ];

        // Count Unit tests (recursively)
        $unitPath = $testsPath . '/Unit';
        if (File::exists($unitPath)) {
            $unitFiles = File::glob($unitPath . '/**/*.php');
            $stats['unit_tests'] = count($unitFiles);
            $stats['test_files'] += count($unitFiles);
        }

        // Count Feature tests
        $featurePath = $testsPath . '/Feature';
        if (File::exists($featurePath)) {
            $featureFiles = File::glob($featurePath . '/**/*.php');
            $stats['feature_tests'] = count($featureFiles);
            $stats['test_files'] += count($featureFiles);
        }

        $stats['total_tests'] = $stats['unit_tests'] + $stats['feature_tests'];

        return response()->json($stats);
    }

    /**
     * Parse test output to extract results
     */
    private function parseTestOutput(string $output): array
    {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total' => 0,
            'duration' => null,
        ];

        // Try to extract test results from Pest/PHPUnit output
        if (preg_match('/(\d+)\s+passed/i', $output, $matches)) {
            $results['passed'] = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s+failed/i', $output, $matches)) {
            $results['failed'] = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s+skipped/i', $output, $matches)) {
            $results['skipped'] = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s+tests/i', $output, $matches)) {
            $results['total'] = (int) $matches[1];
        }
        if (preg_match('/([\d.]+)\s*(?:seconds?|s)/i', $output, $matches)) {
            $results['duration'] = (float) $matches[1];
        }

        // Determine overall status
        $results['status'] = $results['failed'] > 0 ? 'failed' : ($results['passed'] > 0 ? 'passed' : 'unknown');

        return $results;
    }
}

