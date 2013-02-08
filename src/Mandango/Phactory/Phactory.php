<?php
namespace Mandango\Phactory;
use Phactory\Mongo\Phactory as PhactoryBase;

class Phactory extends PhactoryBase {
    public function define($blueprintName, $definition, $defaultsOverride = array(), $associations = array()) 
    {
        $defaults = $this->getDefault($definition);
        return parent::define($blueprintName, $defaults, $associations);
    }

    private function prepareDefinition(array $configClass)
    {
        foreach ($configClass['fields'] as $name => &$field) {
            if (is_string($field)) $field = array('type' => $field);
        }

        foreach ($configClass['fields'] as $name => &$field) {
            if (!is_array($field)) {
                throw new \RuntimeException(sprintf('The field "%s" of the class "%s" is not a string or array.', $name, $this->class));
            }

            if (!isset($field['dbName'])) {
                $field['dbName'] = $name;
            } elseif (!is_string($field['dbName'])) {
                throw new \RuntimeException(sprintf('The dbName of the field "%s" of the class "%s" is not an string.', $name, $this->class));
            }

            if (!isset($field['type'])) {
                throw new \RuntimeException(sprintf('The field "%s" of the class "%s" does not have type.', $name, $this->class));
            }

        }

        unset($field);
        return $configClass;
    }

    private function getDefault(array $configClass) {
        $defaults = array();
        $definition = $this->prepareDefinition($configClass);
        foreach ($definition['fields'] as $name => $field) {
            if ( $default = $this->getDeafaultField($field) ) {
                $defaults = array_merge($defaults, $default);
            }
        }

        return $defaults;
    }

    private function getDeafaultField(array $field) {
        $must = false;
        $value = null;
        if ( isset($field['validation']) ) {
            foreach( $field['validation'] as $validator ) {
                if ( array_key_exists('NotBlank', $validator) ) $must = true;
                else if ( array_key_exists('Choice', $validator) ) {
                    $choices = $validator['Choice']['choices'];
                    $value = $choices[rand(0, count($choices))];
                }

            }   
        }

        if ( !$value ) $value = $this->getDefaultFieldValue($field);

        if ( !$must ) return false;
        return [$field['dbName'] => $value];
    }

    private function getDefaultFieldValue(array $field) {
        switch ($field['type']) {
            case 'string':
                return sprintf('Field %s #$n', $field['dbName']);
            case 'date':
                return new \MongoDate();
            case 'integer':
                return rand(0, 10000);
            case 'float':
                return rand(0, 10000)/100;
            case 'boolean':
                return true;
            default:
                return '';
        }
    }

}