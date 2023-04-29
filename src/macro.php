<?php

declare(strict_types=1);

use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\{
    ElementNotInteractableException,
    WebDriverException,
    ElementNotVisibleException
};

/**
 * Register simple macros for the Laravel Dusk.
 * $field - selector, or @element
 * $value - option value, may be multiple, eg. ['foo', 'bar']
 * $wait  - maximum count of seconds for ajax loading.
 */
Browser::macro('select2', function ($field, $value = null, $wait = 2, $suffix = ' + .select2') {
    /** @var Browser $this */
    $selector = $field.$suffix;
    $element = $this->element($selector);

    if (!$element && !$element->isDisplayed()) {
        throw new InvalidArgumentException("Selector [$selector] not found or not displayed.");
    }

    $highlightedClass    = '.select2-results__option--highlighted';
    $highlightedSelector = '.select2-results__options ' . $highlightedClass;
    $selectableSelector  = '.select2-results__options .select2-results__option';
    $searchSelector      = '.select2-container.select2-container--open .select2-search__field';

    $this->click($selector);

    // if $value equal null, find random element and click him.
    // @todo: may be a couple of times move scroll to down (ajax paging)
    if (null === $value) {
        $this->waitFor($highlightedSelector, $wait);
        $this->script(implode('', [
            "var _dusk_s2_elements = document.querySelectorAll('$selectableSelector');",
            "document.querySelector('$highlightedSelector').classList.remove('$highlightedClass');",
            'var _dusk_s2_el = _dusk_s2_elements[Math.floor(Math.random()*(_dusk_s2_elements.length - 1))];',
            "_dusk_s2_el.classList.add('$highlightedClass');"
        ]));
        $this->click($highlightedSelector);

        return $this;
    }

    // check if search field exists and fill it.
    $element = $this->element($searchSelector);
    $this->waitFor($searchSelector, $wait);

    if ($element->isDisplayed()) {
        try {
            foreach ((array) $value as $item) {
                $element->sendKeys($item);
                $this->waitFor($highlightedSelector, $wait);
                $this->click($highlightedSelector);
            }

            return $this;
        } catch (WebDriverException $exception) {
            if (!$exception instanceof ElementNotInteractableException || !$exception instanceof ElementNotVisibleException) {
                throw $exception;
            }
            // ... otherwise ignore the exception and try another way
        }
    }

    // another way - w/o search field.
    $field = str_replace('\\', '\\\\', $field);
    $this->script("jQuery(\"$field\").val((function () { return jQuery(\"$field option:contains('$value')\").val(); })()).trigger('change')");

    return $this;
});

Browser::macro('select2ExactSearch', function ($field, $value, $wait = 2, $suffix = ' + .select2') {
    /** @var Browser $this */
    $selector = $field.$suffix;
    $element = $this->element($selector);

    if (!$element && !$element->isDisplayed()) {
        throw new InvalidArgumentException("Selector [$selector] not found or not displayed.");
    }

    $highlightedClass    = '.select2-results__option--highlighted';
    $highlightedSelector = '.select2-results__options ' . $highlightedClass;
    $searchSelector      = '.select2-container.select2-container--open .select2-search__field';

    $this->click($selector);

    // check if search field exists and fill it.
    $element = $this->element($searchSelector);
    $this->waitFor($searchSelector, $wait);

    if ($element->isDisplayed()) {
        try {
            foreach ((array) $value as $item) {
                $element->sendKeys($item);
                $this->waitForTextIn($highlightedSelector, $item, $wait);
                $this->click($highlightedSelector);
            }

            return $this;
        } catch (WebDriverException $exception) {
            if (!$exception instanceof ElementNotInteractableException || !$exception instanceof ElementNotVisibleException) {
                throw $exception;
            }
            // ... otherwise ignore the exception and try another way
        }
    }

    // another way - w/o search field.
    $field = str_replace('\\', '\\\\', $field);
    $this->script("jQuery(\"$field\").val((function () { return jQuery(\"$field option:contains('$value')\").val(); })()).trigger('change')");

    return $this;
});

/**
 * Register a macro for Laravel Dusk, which clears the selection from a given Select2
 * $field - the selector of the Select2 element
 * $outerSelector - optionally set an external element, which will be clicked to close the Select2
 */
Browser::macro('select2ClearAll', function ($field, $outerSelector = null, $suffix = ' + .select2') {
    /** @var Browser $this */
    $selector = $field.$suffix;
    $element = $this->element($selector);

    if (!$element && !$element->isDisplayed()) {
        throw new InvalidArgumentException("Selector [$selector] not found or not displayed.");
    }

    $removeButtonClass    = '.select2-selection__clear';
    $removeButtonSelector = $selector . ' ' . $removeButtonClass;

    // Click the remove button
    $this->click($removeButtonSelector);

    // Click outside the select2 to close it
    if ($outerSelector) {
        $this->click($outerSelector);
    }

    return $this;
});
