<?php 
/* $pfre: IncludeCest.php,v 1.1 2016/08/15 12:51:14 soner Exp $ */

/*
 * Copyright (c) 2016 Soner Tari.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. All advertising materials mentioning features or use of this
 *    software must display the following acknowledgement: This
 *    product includes software developed by Soner Tari
 *    and its contributors.
 * 4. Neither the name of Soner Tari nor the names of
 *    its contributors may be used to endorse or promote products
 *    derived from this software without specific prior written
 *    permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once ('Rule.php');

class CommentCest extends Rule
{
	protected $type= 'Comment';
	protected $ruleNumber= 21;
	protected $ruleNumberGenerated= 28;
	protected $sender= 'comment';

	protected $origRule= 'Line1
Line2';
	protected $expectedDispOrigRule= 'Line1
Line2 e u d x';

	protected $modifiedRule= 'Line1
Line2
Line3';
	protected $expectedDispModifiedRule= 'Line1
Line2
Line3
Line4 e u d x';

	function __construct()
	{
		parent::__construct();

		$this->editPageTitle= 'Edit ' . $this->type . ' ' . $this->ruleNumber;
		$this->dLink= NULL;
	}

	/**
	 * @depends testDisplay
	 */
	public function testEditSaveNotModifiedFail(AcceptanceTester $I)
	{
		$this->gotoEditPage($I);

		$I->see($this->editPageTitle, 'h2');
		$I->dontSee('(modified)', 'h2');

		$I->click('Save');

		$I->see($this->editPageTitle, 'h2');
	}

	/**
	 * @depends testEditSaveNotModifiedFail
	 */
	public function testEditSaveNotModifiedForcedFail(AcceptanceTester $I)
	{
		$I->see($this->editPageTitle, 'h2');
		$I->dontSee('(modified)', 'h2');

		$I->checkOption('#forcesave');
		$I->click('Save');

		$I->see($this->editPageTitle, 'h2');
	}

	/**
	 * @depends testEditSaveNotModifiedForcedFail
	 */
	public function testEditModifyRule(AcceptanceTester $I)
	{
		$I->expect('changes are applied incrementally');
		
		$I->see($this->editPageTitle, 'h2');
		$I->dontSee('(modified)', 'h2');

		$this->modifyRule($I);

		$I->see($this->editPageTitle . ' (modified)', 'h2');
	}

	protected function modifyRule(AcceptanceTester $I)
	{
		$I->fillField('#comment', 'Line1
Line2
Line3');
		$I->click('Apply');

		$I->see($this->editPageTitle . ' (modified)', 'h2');

		$I->fillField('#comment', 'Line1
Line2
Line3
Line4');
		$I->click('Apply');

		$I->see($this->editPageTitle . ' (modified)', 'h2');
	}

	/**
	 * @depends testEditModifyRule
	 */
	public function testEditSaveModifiedWithErrorsFail(AcceptanceTester $I)
	{
		$I->click('Save');

		$I->see($this->editPageTitle . ' (modified)', 'h2');
	}

	/**
	 * @depends testEditSaveModifiedWithErrorsFail
	 */
	public function testEditSaveModifiedWithErrorsForced(AcceptanceTester $I)
	{
		$I->checkOption('#forcesave');
		$I->click('Save');

		$I->dontSee($this->editPageTitle, 'h2');
	}

	/**
	 * @depends testDisplayModifiedWithErrorsForced
	 */
	public function testEditRevertModifications(AcceptanceTester $I)
	{
		$this->gotoEditPage($I);

		$I->expect('incrementally reverting modifications brings us back to the original rule');

		$I->see($this->editPageTitle, 'h2');
		$I->dontSee('(modified)', 'h2');

		$this->revertModifications($I);

		$I->see($this->editPageTitle . ' (modified)', 'h2');
	}

	protected function revertModifications(AcceptanceTester $I)
	{
		$I->fillField('#comment', 'Line1
Line2
Line3');
		$I->click('Apply');

		$I->see($this->editPageTitle . ' (modified)', 'h2');

		$I->fillField('#comment', 'Line1
Line2');
		$I->click('Apply');

		$I->see($this->editPageTitle . ' (modified)', 'h2');
	}

	/**
	 * @depends testEditRevertModifications
	 */
	public function testEditBackToModifiedRule(AcceptanceTester $I)
	{
		$I->expect('modifying again brings us back to the saved modified rule, (modified) should disappear');

		$this->modifyRuleQuick($I);

		$I->see($this->editPageTitle, 'h2');
		$I->dontSee('(modified)', 'h2');
	}

	protected function modifyRuleQuick(AcceptanceTester $I)
	{
		$I->fillField('#comment', 'Line1
Line2
Line3
Line4');
		$I->click('Apply');
	}

	/**
	 * @depends testEditBackToModifiedRule
	 * @after logout
	 */
	public function testDisplayGeneratedModifiedWithErrors(AcceptanceTester $I)
	{
		$I->expect('modified rule with errors is generated on Display page correctly');

		$I->click('Display & Install');
		$I->wait($this->tabSwitchInterval);
		$I->seeInCurrentUrl('conf.php?submenu=displayinstall');
		$I->see('Display line numbers');

		$I->dontSee(' ' . $this->ruleNumberGenerated . ': # Line1
  ' . ($this->ruleNumberGenerated + 1) . ': # Line2
  ' . ($this->ruleNumberGenerated + 2) . ': # Line3
  ' . ($this->ruleNumberGenerated + 3) . ': # Line4', '#rules');

		$I->checkOption('#forcedisplay');

		$I->see(' ' . $this->ruleNumberGenerated . ': # Line1
  ' . ($this->ruleNumberGenerated + 1) . ': # Line2
  ' . ($this->ruleNumberGenerated + 2) . ': # Line3
  ' . ($this->ruleNumberGenerated + 3) . ': # Line4', '#rules');
	}
}
?>