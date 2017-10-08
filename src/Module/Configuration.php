<?php

namespace CW\Module;

class Configuration
{
    /**
     * @var Module
     */
    protected $module;

    /**
     * Register Module.
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * Get configuration page content.
     */
    public function getContent(): string
    {
        return \Context::getContext()->controller->renderOptions();
    }

    /**
     * Get option value.
     */
    public function getOptionValue(string $option): string
    {
        return \Configuration::get($this->getOptionName($option));
    }

    /**
     * Get options values.
     */
    public function getOptionsValues(array $options): array
    {
        $keys   = array_map('strtolower', $options);
        $values = array_map([$this, 'getOptionValue'], $options);

        return array_combine($keys, $values);
    }

    /**
     * Set option default value.
     */
    public function setOptionDefaultValue(string $option): bool
    {
        if (!$this->hasOptionDefaultValue($option)) {
            return true;
        }

        return \Configuration::updateValue(
            $this->getOptionName($option),
            $this->getOptionDefaultValue($option)
        );
    }

    /**
     * Set options default values.
     */
    public function setOptionsDefaultValues(array $options): bool
    {
        return array_product(array_map([$this, 'setOptionDefaultValue'], $options));
    }

    /**
     * Remove option value.
     */
    public function removeOptionValue(string $option): bool
    {
        return \Configuration::deleteByName($this->getOptionName($option));
    }

    /**
     * Remove options values.
     */
    public function removeOptionsValues(array $options): bool
    {
        return array_product(array_map([$this, 'removeOptionValue'], $options));
    }

    /**
     * Set configuration form options and action URL.
     */
    public function hookActionAdminModulesOptionsModifier(array $params)
    {
        if (!$this->isConfigurationPage()) {
            return;
        }
        $params['options'] = $this->getOptionsForm($this->module::OPTIONS);
        $params['option_vars']['current'] = $this->getConfigurationPageUrl();
    }

    /**
     * Get options form.
     */
    protected function getOptionsForm(array $options): array
    {
        return [[
            'fields' => $this->getOptionsFields($options),
            'submit' => ['title' => $this->l('Save')],
        ]];
    }

    /**
     * Get configuration page URL.
     */
    protected function getConfigurationPageUrl(): string
    {
        return \AdminController::$currentIndex.'&configure='.$this->module->name;
    }

    /**
     * Get options fields.
     */
    protected function getOptionsFields(array $options): array
    {
        $names  = $this->getOptionsNames(array_keys($options));
        $params = $this->getOptionsFieldsParams($options);

        return array_combine($names, $params);
    }

    /**
     * Get options names.
     */
    protected function getOptionsNames(array $options): array
    {
        return array_map([$this, 'getOptionName'], $options);
    }

    /**
     * Get option name.
     */
    protected function getOptionName(string $option): string
    {
        return strtoupper($this->module->name.'_'.$option);
    }

    /**
     * Get options fields parameters.
     */
    protected function getOptionsFieldsParams(array $options): array
    {
        return array_map([$this, 'getOptionFieldParams'], $options, array_keys($options));
    }

    /**
     * Get option field parameters.
     *
     * @todo translate list/choices values (preserve list keys!).
     */
    protected function getOptionFieldParams(array $params, string $option): array
    {
        foreach ($params as $key => &$value) {
            switch ($key) {
                case 'title':
                case 'desc':
                    $value = $this->l($value);
                    break;
                case 'list':
                    $value = $value ? $this->l($value) : $this->module->getOptionList($option);
                    break;
                case 'choices':
                    $value = $value ? $this->l($value) : $this->module->getOptionChoices($option);
                    break;
                default:
                    continue;
            }
        }

        return $params;
    }

    /**
     * Get option default value.
     */
    protected function getOptionDefaultValue(string $option): string
    {
        return $this->module::OPTIONS[$option]['default'];
    }

    /**
     * Get value from $_GET/$_POST.
     */
    protected function getValue(string $key, string $default = ''): string
    {
        return \Tools::getValue($key, $default);
    }

    /**
     * Wether or not an option has a default value.
     */
    protected function hasOptionDefaultValue(string $option): bool
    {
        return $this->module::OPTIONS[$option]['default'] ?? false;
    }

    /**
     * Wether or not configuration page is loading.
     */
    protected function isConfigurationPage(): bool
    {
        return $this->module->name === $this->getValue('configure');
    }

    /**
     * Get translated string(s).
     *
     * @param mixed $value
     */
    protected function l($value)
    {
        if (is_string($value)) {
            return $this->module->l($value);
        }

        $strings = [];
        foreach ($value as $key => $string) {
            $strings[$key] = $this->l($string);
        }

        return $strings;
    }
}
