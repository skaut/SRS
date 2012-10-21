<?php

/**
 * Homepage presenter.
 */

class HomepagePresenter extends \SRS\BasePresenter
{

	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
	}

}
