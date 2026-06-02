<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class GenerateApiCollectionCommand extends Command
{
    protected $signature = 'api:export-collection';

    protected $description = 'Scan project routes and FormRequests to generate a Postman/Hoppscotch compatible JSON Collection API.';

    public function handle()
    {
        $this->info('Scanning routes and FormRequests...');

        $collection = [
            'info' => [
                'name' => config('app.name', 'ViecVat').' API Collection',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        $routes = Route::getRoutes();
        $folders = [];

        foreach ($routes as $route) {
            $uri = $route->uri();

            // Only care about API routes
            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $segments = explode('/', $uri);
            $level1Str = isset($segments[1]) ? strtolower($segments[1]) : 'other';
            $level2Str = isset($segments[2]) ? strtolower($segments[2]) : null;

            if (in_array($level1Str, ['admin', 'customer', 'worker', 'user'])) {
                $level1Name = ucfirst($level1Str);
                $level2Name = $level2Str ? ucfirst($level2Str) : 'General';
            } else {
                $level1Name = 'Common';
                $level2Name = ucfirst($level1Str);
            }

            if (! isset($folders[$level1Name])) {
                $folders[$level1Name] = [
                    'name' => $level1Name,
                    'item' => [],
                ];
            }

            // Find or create level 2 target folder
            $level2Exists = false;
            foreach ($folders[$level1Name]['item'] as $f) {
                if ($f['name'] === $level2Name) {
                    $level2Exists = true;
                    break;
                }
            }
            if (! $level2Exists) {
                $folders[$level1Name]['item'][] = [
                    'name' => $level2Name,
                    'item' => [],
                ];
            }

            $method = $route->methods()[0];

            // Basic route item skeleton
            $routeItem = [
                'name' => "[$method] $uri",
                'request' => [
                    'method' => $method,
                    'url' => [
                        'raw' => '{{base_url}}/'.$uri,
                        'host' => ['{{base_url}}'],
                        'path' => array_values(array_filter($segments)),
                        'query' => [],
                    ],
                    'header' => [
                        ['key' => 'Accept', 'value' => 'application/json'],
                        ['key' => 'Content-Type', 'value' => 'application/json'],
                    ],
                    'auth' => [
                        'type' => 'bearer',
                        'bearer' => [
                            [
                                'key' => 'token',
                                'value' => '{{access_token}}',
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ];

            // Use reflection to extract Request parameter rules
            $action = $route->getAction('uses');
            if (is_string($action) && str_contains($action, '@')) {
                [$controller, $methodName] = explode('@', $action);
                if (class_exists($controller) && method_exists($controller, $methodName)) {
                    try {
                        $refMethod = new \ReflectionMethod($controller, $methodName);
                        foreach ($refMethod->getParameters() as $param) {
                            $type = $param->getType();
                            if ($type && ! $type->isBuiltin()) {
                                $className = $type->getName();
                                if (is_subclass_of($className, \Illuminate\Foundation\Http\FormRequest::class)) {
                                    $this->extractRulesToDummyData($className, $routeItem);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // ignore reflection errors
                    }
                }
            }

            // Cleanup query params if empty
            if (empty($routeItem['request']['url']['query'])) {
                unset($routeItem['request']['url']['query']);
            }

            // Append routeItem to the correct Level 2 folder
            foreach ($folders[$level1Name]['item'] as &$f) {
                if ($f['name'] === $level2Name) {
                    $f['item'][] = $routeItem;
                    break;
                }
            }
            unset($f);
        }

        $collection['item'] = array_values($folders);

        // Store to storage path
        $path = storage_path('app/api_collection.json');
        file_put_contents($path, json_encode($collection, JSON_PRETTY_PRINT));

        $this->info("Generation complete! File saved to: {$path}");
        $this->info('You can import this file directly into Hoppscotch or Postman.');
    }

    /**
     * Extracts validation rules from FormRequest and sets dummy data onto the route item structure
     */
    protected function extractRulesToDummyData($requestClass, &$routeItem)
    {
        try {
            $request = new $requestClass;
            $rules = [];

            if (method_exists($request, 'rules')) {
                // Dependency injection into rules() method
                $rules = app()->call([$request, 'rules']);
            }

            if (empty($rules)) {
                return;
            }

            $dummyData = [];
            foreach ($rules as $field => $fieldRules) {
                if (is_string($fieldRules)) {
                    $fieldRules = explode('|', $fieldRules);
                }

                if (! is_array($fieldRules)) {
                    continue;
                }

                // We mock arrays by taking the base field key. E.g "items" vs "items.*"
                if (str_contains($field, '.*')) {
                    $baseField = explode('.*', $field)[0];
                    $val = [$this->generateDummyValue($fieldRules)];
                    Arr::set($dummyData, $baseField, $val);
                } else {
                    Arr::set($dummyData, $field, $this->generateDummyValue($fieldRules));
                }
            }

            $httpMethod = $routeItem['request']['method'];

            if ($httpMethod === 'GET' || $httpMethod === 'DELETE') {
                // Populate query parameters
                foreach (Arr::dot($dummyData) as $key => $val) {
                    $routeItem['request']['url']['query'][] = [
                        'key' => $key,
                        'value' => is_array($val) ? json_encode($val) : (string) $val,
                    ];
                }
            } else {
                // Populate request body (JSON)
                $routeItem['request']['body'] = [
                    'mode' => 'raw',
                    'raw' => json_encode($dummyData, JSON_PRETTY_PRINT),
                    'options' => [
                        'raw' => ['language' => 'json'],
                    ],
                ];
            }
        } catch (\Exception $e) {
            // Ignore errors (like auth missing on validation)
        }
    }

    /**
     * Generates a single fake value based on a given set of validation rules
     */
    protected function generateDummyValue($rules)
    {
        // Simple type inference based on laravel rules presence
        $rulesStr = implode(',', $rules);

        if (str_contains($rulesStr, 'email')) {
            return 'user@example.com';
        }
        if (str_contains($rulesStr, 'uuid')) {
            return (string) \Illuminate\Support\Str::uuid();
        }
        if (str_contains($rulesStr, 'integer') || str_contains($rulesStr, 'numeric')) {
            return 1;
        }
        if (str_contains($rulesStr, 'boolean')) {
            return true;
        }
        if (str_contains($rulesStr, 'array')) {
            return [];
        }
        if (str_contains($rulesStr, 'date')) {
            return date('Y-m-d');
        }

        return 'string_value';
    }
}
