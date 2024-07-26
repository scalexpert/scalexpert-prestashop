<?php

namespace ScalexpertPlugin\Service;

class SolutionSorterService
{
    public function sortSolutionsByPosition(&$solutions): void
    {
        uasort($solutions, function ($a, $b) {
            return $a['position'] > $b['position'];
        });
    }

    public function sortSolutionsByDuration(&$solutions): void
    {
        uasort($solutions, function ($a, $b) {
            return $a['duration'] > $b['duration'];
        });
    }
}
