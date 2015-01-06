<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Service;

use Diff;
use Diff_Renderer_Text_Unified;
use ROH\Bundle\StackManagerBundle\Model\Parameters;
use ROH\Bundle\StackManagerBundle\Model\Template;

/**
 * Service to compare the parameters and template of two stack models.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackComparisonService
{
    /**
     * Compare two stack parameters models.
     *
     * @param Parameters $parametersA First parameters model to compare.
     * @param Parameters $parametersB Second parameters model to compare.
     * @return string Rendered unified diff of the changes between the first
     *     and second parameters models.
     */
    public function compareParameters(Parameters $parametersA, Parameters $parametersB)
    {
        /**
         * Convert the parameters object to a numerically indexed array of
         * strings representing each of the parameters (name and value).
         *
         * @param $parameters Parameters
         * @return array
         */
        $parametersToList = function ($parameters) {
            $list = [];
            foreach ($parameters as $key => $value) {
                $list[] = $key . ' = ' . $value;
            }

            return $list;
        };

        $diff = new Diff(
            $parametersToList($parametersA),
            $parametersToList($parametersB),
            []
        );

        return $diff->render(new Diff_Renderer_Text_Unified);
    }

    /**
     * Compare two stack template models.
     *
     * @param Template $templateA First template model to compare.
     * @param Template $templateB Second template model to compare.
     * @return string Rendered unified diff of the changes between the first
     *     and second template models.
     */
    public function compareTemplate(Template $templateA, Template $templateB)
    {
        // The difference engine expects an array of strings, so get the
        // template bodies as JSON and explode in to separate lines.
        $diff = new Diff(
            explode("\n", $templateA->getBodyJSON()),
            explode("\n", $templateB->getBodyJSON()),
            []
        );

        return $diff->render(new Diff_Renderer_Text_Unified);
    }
}
