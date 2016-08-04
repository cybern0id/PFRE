<?php
/* $pfre: Route.php,v 1.5 2016/08/03 01:12:23 soner Exp $ */

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

class Route extends NatBase
{
	function __construct($str)
	{
		$this->keywords = array(
			'route-to' => array(
				'method' => 'parseRoute',
				'params' => array(),
				),
			'reply-to' => array(
				'method' => 'parseRoute',
				'params' => array(),
				),
			'dup-to' => array(
				'method' => 'parseRoute',
				'params' => array(),
				),
			);

		parent::__construct($str);
	}

	function parseRoute()
	{
		$this->rule['type']= $this->words[$this->index];
		// @todo routehost not redirhost
		$this->parseItems('redirhost');
	}

	function generate()
	{
		$this->genAction();

		$this->genFilterHead();
		$this->genFilterOpts();

		$this->genValue('type');
		$this->genItems('redirhost');
		$this->genPoolType();

		$this->genComment();
		$this->str.= "\n";
		return $this->str;
	}

	function display($rulenumber, $count)
	{
		$this->dispHead($rulenumber);
		$this->dispAction();
		$this->dispValue('direction', 'Direction');
		$this->dispInterface();
		$this->dispLog();
		$this->dispKey('quick', 'Quick');
		$this->dispValue('proto', 'Proto');
		$this->dispSrcDest();
		$this->dispValue('type', 'Type');
		$this->dispValue('redirhost', 'Redirect Host');
		$this->dispTail($rulenumber, $count);
	}

	function input()
	{
		$this->inputAction();

		$this->inputFilterHead();

		$this->inputLog();
		$this->inputBool('quick');

		$this->inputKey('type');
		$this->inputKey('redirhost');

		$this->inputFilterOpts();

		$this->inputKey('comment');
		$this->inputDelEmpty();
	}

	function edit($rulenumber, $modified, $testResult, $action)
	{
		$this->index= 0;
		$this->rulenumber= $rulenumber;

		$this->editHead($modified);

		$this->editAction();

		$this->editFilterHead();

		$this->editLog();
		$this->editCheckbox('quick', 'Quick');

		$this->editRouteOption();
		$this->editText('redirhost', 'Redirect Host', 'Nat', NULL, 'ip, host, table or macro');

		$this->editFilterOpts();

		$this->editComment();
		$this->editTail($modified, $testResult, $action);
	}

	function editRouteOption()
	{
		?>
		<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
			<td class="title">
				<?php echo _TITLE('Route Option').':' ?>
			</td>
			<td>
				<select id="type" name="type">
					<option value="dup-to" <?php echo ($this->rule['type'] == 'dup-to' ? 'selected' : ''); ?>>dup-to</option>
					<option value="reply-to" <?php echo ($this->rule['type'] == 'reply-to' ? 'selected' : ''); ?>>reply-to</option>
					<option value="route-to" <?php echo ($this->rule['type'] == 'route-to' ? 'selected' : ''); ?>>route-to</option>
				</select>
				<?php $this->editHelp($this->rule['type']) ?>
			</td>
		</tr>
		<?php
	}
}
?>
