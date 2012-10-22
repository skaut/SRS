<?php

/**
 * Homepage presenter.
 */
namespace BackModule;
class DashboardPresenter extends \SRS\BasePresenter
{

	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
	}

}
