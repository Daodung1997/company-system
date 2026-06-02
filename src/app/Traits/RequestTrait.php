<?php

namespace App\Traits;

use App\Constants\Commons\Rule;

trait RequestTrait
{
    /**
     * @throws \Exception
     */
    public function renderMessageFromRule($rule): array
    {

        $filteredArray = array_filter($rule, function ($value, $key) {
            return ! str_contains($key, '*');
        }, ARRAY_FILTER_USE_BOTH);

        $newArray = [];

        foreach ($filteredArray as $key => $rules) {
            $rulesList = is_string($rules) ? explode('|', $rules) : $rules;
            foreach ($rulesList as $rule) {
                if (is_string($rule)) {
                    switch (true) {
                        case str_contains($rule, Rule::UNIQUE):
                            $newArray["{$key}.".Rule::UNIQUE] = "{$key}.".Rule::UNIQUE;
                            break;
                        case str_contains($rule, Rule::EXISTS):
                            $newArray["{$key}.".Rule::EXISTS] = "{$key}.".Rule::EXISTS;
                            break;
                        case str_contains($rule, Rule::MAX):
                            $newArray["{$key}.".Rule::MAX] = "{$key}.".Rule::MAX;
                            break;
                        case str_contains($rule, Rule::MIN):
                            $newArray["{$key}.".Rule::MIN] = "{$key}.".Rule::MIN;
                            break;
                        case str_contains($rule, Rule::REGEXP):
                            $newArray["{$key}.".Rule::REGEXP] = "{$key}.".Rule::REGEXP;
                            break;
                        case str_contains($rule, Rule::REQUIRED):
                            $newArray["{$key}.".Rule::REQUIRED] = "{$key}.".Rule::REQUIRED;
                            break;
                        default:
                            $newArray["{$key}.".strtolower($rule)] = "{$key}.".strtolower($rule);
                    }
                } else {
                    $rule = $this->identifyRule($rule);
                    $newArray["{$key}.".strtolower($rule)] = "{$key}.".strtolower($rule);
                }
            }
        }

        return $newArray;
    }

    /**
     * @throws \Exception
     */
    public function renderMessageFromRulesWithChild($rule, $count, $keyRule): array
    {
        $filteredArray = array_filter($rule, function ($value, $key) use ($keyRule) {
            return str_contains($key, '*') && str_contains($key, $keyRule);
        }, ARRAY_FILTER_USE_BOTH);

        $newArray = [];
        $i = 0;
        foreach ($filteredArray as $rules) {
            $rulesList = is_string($rules) ? explode('|', $rules) : $rules;

            foreach ($rulesList as $rule) {
                $ruleType = $this->identifyRule($rule);

                if ($ruleType) {
                    $newArray["code.$i.$ruleType"] = "code.$i.$ruleType";
                }
            }
        }

        return $newArray;
    }

    /**
     * @throws \Exception
     */
    public function identifyRule($rule): ?string
    {
        if (is_string($rule)) {
            return strtolower($rule);
        }

        if (is_object($rule)) {
            $ruleString = method_exists($rule, '__toString') ? (string) $rule : '';

            $ruleListEnums = Rule::LIST_RULES;
            foreach ($ruleListEnums as $ruleData) {
                if (str_contains($ruleString, $ruleData)) {
                    return $ruleData;
                }
            }
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function generateMessages(): array
    {
        $messages = [];
        foreach ($this->rules() as $field => $rules) {
            foreach ($rules as $rule) {
                $ruleName = is_string($rule) ? explode(':', $rule)[0] : (is_object($rule) ? class_basename($rule) : $rule);

                if (str_contains($field, '.*.')) {
                    $parts = explode('.*.', $field);
                    $baseField = $parts[0];
                    $subField = end($parts);

                    if (str_contains($subField, '.')) {
                        $nestedParts = explode('.', $subField);
                        $lastField = end($nestedParts);
                        $messages["{$field}.{$ruleName}"] = "{$baseField}[:index].{$lastField}.{$ruleName}";
                    } else {
                        $messages["{$field}.{$ruleName}"] = "{$baseField}[:index].{$subField}.{$ruleName}";
                    }
                } else {
                    $messages["{$field}.{$ruleName}"] = "{$field}.{$ruleName}";
                }
            }
        }

        return $messages;
    }
}
