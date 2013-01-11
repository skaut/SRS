<?php

/**
 * Homepage presenter.
 */
namespace FrontModule;

class PagePresenter extends \SRS\BasePresenter
{

	public function renderDefault($page)
	{
        \Nette\Diagnostics\Debugger::dump($page);
		$this->template->anyVariable = 'any value';
	}

}
