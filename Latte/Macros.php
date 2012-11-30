<?php

namespace Mishak\WebResourceManagement\Latte;

use Nette;
use Nette\Forms\Form;
use Nette\Latte;
use Nette\Latte\PhpWriter;
use Nette\Latte\MacroNode;

class Macros extends Latte\Macros\MacroSet
{

	/**
	 * @param \Nette\Latte\Compiler $compiler
	 * @return \Nette\Latte\Macros\MacroSet|void
	 */
	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);

		$me->addMacro('scripts', array($me, 'macroScripts'));
		$me->addMacro('styles', array($me, 'macroStyles'));
		$me->addMacro('script', array($me, 'macroScript'));
		$me->addMacro('style', array($me, 'macroStyle'));
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroScripts(MacroNode $node, PhpWriter $writer)
	{
		$output = $writer->write('echo $presenter->getContext()->getByType(%var)->setPresenter($presenter)', 'Mishak\WebResourceManagement\IScriptManager');
		if ($node->tokenizer->hasNext()) {
			$output .= $writer->write('->add(%node.array)');
		}
		return $output . $writer->write('->output()');
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroStyles(MacroNode $node, PhpWriter $writer)
	{
		$output = $writer->write('echo $presenter->getContext()->getByType(%var)->setPresenter($presenter)', 'Mishak\WebResourceManagement\IStyleManager');
		if ($node->tokenizer->hasNext()) {
			$output .= $writer->write('->add(%node.array)');
		}
		return $output . $writer->write('->output()');
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroScript(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$presenter->getContext()->getByType(%var)->add(%node.array)', 'Mishak\WebResourceManagement\IScriptManager');
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroStyle(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$presenter->getContext()->getByType(%var)->add(%node.array)', 'Mishak\WebResourceManagement\IStyleManager');
	}

}
