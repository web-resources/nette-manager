<?php

namespace WebResources\NetteManager\Latte;

use Nette\Latte\Compiler;
use Nette\Latte\Macros\MacroSet;
use Nette\Latte\PhpWriter;
use Nette\Latte\MacroNode;



class Macros extends MacroSet
{

	/**
	 * @param \Nette\Latte\Compiler $compiler
	 * @return \Nette\Latte\Macros\MacroSet|void
	 */
	public static function install(Compiler $compiler)
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
		$output = $writer->write('echo $presenter->getContext()->getByType(%var)->setPresenter($presenter)', 'WebResources\NetteManager\IScriptManager');
		if ($node->tokenizer->isNext()) {
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
		$output = $writer->write('echo $presenter->getContext()->getByType(%var)->setPresenter($presenter)', 'WebResources\NetteManager\IStyleManager');
		if ($node->tokenizer->isNext()) {
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
		return $writer->write('$presenter->getContext()->getByType(%var)->add(%node.array)', 'WebResources\NetteManager\IScriptManager');
	}


	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroStyle(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$presenter->getContext()->getByType(%var)->add(%node.array)', 'WebResources\NetteManager\IStyleManager');
	}

}
