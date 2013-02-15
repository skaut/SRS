<?php

/**
 * Homepage presenter.
 */
namespace FrontModule;

class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
	}

}
