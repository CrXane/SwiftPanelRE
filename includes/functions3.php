<?php

function renderMessageBox(): string
{
	$html = '';

	if (isset($_SESSION['msg1'])) {
		$html = '<div id="infobox"><strong>' .
			$_SESSION['msg1'] .
			'</strong><br />' .
			($_SESSION['msg2'] ?? '') .
			'</div>';
	}

	unset($_SESSION['msg1'], $_SESSION['msg2']);

	return $html;
}

function renderFormFields(array $inputs): string
{
	$html = '<fieldset><table width="100%" border="0" cellpadding="2" cellspacing="2">';

	foreach ($inputs as $name => $def) {
		$value = $_SESSION[$name] ?? '';
		unset($_SESSION[$name]);

		[$type, $label] = $def;

		switch ($type) {
			case 'text':
			case 'password':
				$size = $def[2] ?? 20;
				$hint = $def[3] ?? '';
				$html .= '
				<tr>
					<td class="fieldname" style="width:140px;">' . $label . '</td>
					<td class="fieldarea">
						<input type="' . $type . '" name="' . $name . '" class="text" size="' . $size . '" value="' . htmlspecialchars($value) . '" />
						' . ($hint ? '<font color="#666666" size="-2">' . $hint . '</font>' : '') . '
					</td>
				</tr>';
				break;

			case 'radio':
				$html .= '<tr><td class="fieldname" style="width:140px;">' . $label . '</td><td class="fieldarea">';
				foreach ($def[2] as $id => $val) {
					$checked = ($value == $val) ? 'checked' : '';
					$html .= '<label>
						<input type="radio" name="' . $name . '" value="' . $val . '" ' . $checked . ' /> ' . $val . '
					</label>&nbsp;&nbsp;';
				}
				$html .= '</td></tr>';
				break;

			case 'select':
				$html .= '<tr><td class="fieldname" style="width:140px;">' . $label . '</td><td class="fieldarea">';
				if (empty($def[2]) && isset($def[3])) {
					$html .= $def[3];
				} else {
					$html .= '<select name="' . $name . '" class="select">';
					foreach ($def[2] as $k => $v) {
						$selected = ($value == $k) ? 'selected' : '';
						$html .= '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
					}
					$html .= '</select>';
				}
				$html .= '</td></tr>';
				break;

			case 'textarea':
				$cols = $def[2] ?? 40;
				$rows = $def[3] ?? 5;
				$html .= '
				<tr>
					<td class="fieldname" style="width:140px;">' . $label . '</td>
					<td class="fieldarea">
						<textarea name="' . $name . '" class="textarea" cols="' . $cols . '" rows="' . $rows . '">' .
						htmlspecialchars($value) .
						'</textarea>
					</td>
				</tr>';
				break;

			case 'checkbox':
				$enabled = ($value === 'on');
				$html .= '
				<tr>
					<td class="fieldname" style="width:140px;color:' . ($enabled ? '#669933' : '#DD0000') . '">
						' . ($enabled ? 'Enabled' : 'Disabled') . '
					</td>
					<td class="fieldarea">
						<label>
							<input type="checkbox" name="' . $name . '" class="checkbox" ' . ($enabled ? 'checked' : '') . ' />
							' . $label . '
						</label>
					</td>
				</tr>';
				break;

			case 'divider':
				$html .= '</table></fieldset><fieldset><table width="100%" border="0" cellpadding="2" cellspacing="2">';
				break;

			case 'content':
				$html .= '
				<tr>
					<td class="fieldname" style="width:140px;">' . $label . '</td>
					<td class="fieldarea">' . ($def[2] ?? '') . '</td>
				</tr>';
				break;

			case 'cfg':
				$count = $def[1];
				$editable = !$def[2];

				for ($n = 1; $n <= $count; $n++) {
					$cfgName = $_SESSION["cfg{$n}name"] ?? '';
					$cfgVal  = $_SESSION["cfg{$n}"] ?? '';
					$cfgEdit = $_SESSION["cfg{$n}edit"] ?? '';

					unset($_SESSION["cfg{$n}name"], $_SESSION["cfg{$n}"], $_SESSION["cfg{$n}edit"]);

					$html .= '
					<tr>
						<td class="fieldname" style="width:140px;">' .
						($editable
							? '<input type="text" name="cfg' . $n . 'name" class="text" size="15" value="' . htmlspecialchars($cfgName) . '" />'
							: htmlspecialchars($cfgName)
						) .
						'</td>
						<td class="fieldarea">
							<input type="text" name="cfg' . $n . '" class="text" size="15" value="' . htmlspecialchars($cfgVal) . '" />
							<font color="#666666" size="-2">{cfg' . $n . '}</font>
							<label>
								<input type="checkbox" name="cfg' . $n . 'edit" class="checkbox" ' . ($cfgEdit === 'on' ? 'checked' : '') . ' />
								Allow client to edit
							</label>
						</td>
					</tr>';
				}
				break;
		}
	}

	$html .= '</table></fieldset>';
	return $html;
}

function renderFormButtons(array $buttons): string
{
	$html = '<br /><div align="center">';

	foreach ($buttons as $label => $def) {
		switch ($def[0]) {
			case 'submit':
				$html .= '<input type="submit" value="' . $label . '" class="button green" />';
				break;
			case 'reset':
				$html .= '<input type="reset" value="' . $label . '" class="button red" />';
				break;
			case 'link':
				$html .= '<input type="button" value="' . $label . '" class="button" onclick="window.location=\'' . $def[1] . '\'" />';
				break;
		}
	}

	return $html . '</div>';
}

function renderForm(array $form): void
{
	$method = $form[6] ? 'get' : 'post';

	echo '<form method="' . $method . '" action="' . $form[0] . '">';

	if (!empty($form[1])) {
		foreach ($form[1] as $k => $v) {
			echo '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars($v) . '" />';
		}
	}

	if ($form[4]) {
		echo renderMessageBox();
	}

	if ($form[5]) {
		echo '<div style="font-size:18px;">' . $form[5] . '</div>';
	}

	echo '<br />';
	echo renderFormFields($form[2]);
	echo renderFormButtons($form[3]);
	echo '</form>';
}

function renderTabs(array $tabs, int $active): void
{
	echo '<table width="100%" border="0" cellpadding="1" cellspacing="0"><tr>';
	echo '<td width="5" class="tabspacer"></td>';

	$n = 1;
	foreach ($tabs as $label => $url) {
		$class = ($n === $active) ? 'tabsactive' : 'tabs';
		echo '<td class="' . $class . '" onclick="window.location=\'' . $url . '\'">' . $label . '</td>';
		echo '<td width="2" class="tabspacer"></td>';
		$n++;
	}

	echo '<td width="100%" class="tabspacer">&nbsp;</td>';
	echo '</tr></table>';
}