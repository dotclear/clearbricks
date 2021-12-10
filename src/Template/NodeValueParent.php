<?php
/**
 * @class NodeValueParent
 * @brief Value node, for all {{tpl:Tag}}
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Template;

class NodeValueParent extends NodeValue
{
    public function compile(Template $tpl): string
    {
        // simply ask currently being displayed to display itself!
        return NodeBlockDefinition::renderParent($tpl);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Template\NodeValueParent', 'tplNodeValueParent');
