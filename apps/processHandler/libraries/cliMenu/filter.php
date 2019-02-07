<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

use Codedungeon\PHPCliColors\Color;

class filter
{
    private $filterBy;

    private $searchQuery = '';

    public function __construct($filterBy)
    {
        $this->filterBy = $filterBy;
    }

    public function handleUserInput(menu $menu, $input)
    {
        if ($input === 127) {
            $this->removeSearchQueryLastChar();
            $this->update($menu);

            return;
        }

        if (!ctype_graph($input)) {
            return;
        }

        $input = dechex($input);

        if (strlen($input) === 1) {
            $input = "0{$input}";
        }

        $input = hex2bin($input);
        $this->addToSearchQuery($input);
        $this->update($menu);
    }

    private function addToSearchQuery($input)
    {
        $this->searchQuery .= $input;
    }

    private function removeSearchQueryLastChar()
    {
        $this->searchQuery = substr($this->searchQuery, 0, -1);
    }

    private function update(menu $menu)
    {
        $menuItems = $menu->getMenuItemsOrigin();
        $matched = false;

        if (empty($this->searchQuery)) {
            $menu->setMenuItems($menuItems);
            $menu->resetHeaderInfo();

            return;
        }

        foreach ($menuItems as $key => $menuItem) {
            if (!$this->checkCondition($menuItem)) {
                unset($menuItems[$key]);
                continue;
            }

            $matched = true;
        }

        $menuItems = array_values($menuItems);

        if (!$matched) {
            $menuItems = $menu->getMenuItemsOrigin();
            $menu->setHeaderInfo(Color::BG_RED . "Nothing found by query: {$this->searchQuery}" . Color::RESET);
        } else {
            $menu->setHeaderInfo(Color::BG_GREEN . 'Found ' . count($menuItems) . " items by query: {$this->searchQuery}" . Color::RESET);
        }

        $menu->setMenuItems($menuItems);
    }

    private function checkCondition(menuItem $menuItem)
    {
        $haystack = !empty($this->filterBy) ? $menuItem->getValue()[$this->filterBy] : $menuItem->getTitle();

        if (is_array($haystack)) {
            $haystack = json_encode($haystack);
        }

        if (mb_stripos($haystack, $this->searchQuery) !== false) {
            return true;
        }

        return false;
    }
}