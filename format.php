<?php
/**
 * PunBB formatting
 *
 * @copyright (C) 2008-2009 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */
if ( !function_exists('forum_htmlencode') ) :
function forum_htmlencode($str)
{
	return htmlspecialchars($str);
}
endif;

if ( !function_exists('forum_trim') ) :
function forum_trim($str, $charlist = " \t\n\r\x0b\xc2\xa0")
{
	return trim($str, $charlist);
}
endif;

if ( !function_exists('escape_cdata') ) :
function forum_linebreaks($str)
{
	return str_replace(array("\r\n", "\r"), "\n", $str);
}
endif;

if ( !function_exists('escape_cdata') ) :
function escape_cdata($str)
{
	return str_replace(']]>', ']]&gt;', $str);
}
endif;

if ( !function_exists('parse_signature') ) :
function parse_signature($text) {
	$text = forum_htmlencode($text);
	$text = do_bbcode($text);

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$text = str_replace($pattern, $replace, $text);

	return $text;
}
endif;

if ( !function_exists('parse_message') ) :
function parse_message($text) {
	$text = forum_htmlencode($text);
	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false) {
		list($inside, $outside) = split_text($text, '[code]', '[/code]');
		$text = implode("\0", $outside);
	}

	$text = do_bbcode($text);

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$text = str_replace($pattern, $replace, $text);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside)) {
		$outside = explode("\0", $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i) {
			$text .= $outside[$i];
			if (isset($inside[$i])) {
				$text .= '<div class="sfcode">'.forum_trim($inside[$i], "\n\r").'</div>';
			}
		}
	}

	return $text;
}
endif;

if ( !function_exists('preparse_bbcode') ) :
function preparse_bbcode($text) {
	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]');
		$text = implode("\0", $outside);
	}

	// Tidy up lists
	$pattern = array('/\[list(?:=([1a\*]))?\]((?>(?:(?!\[list(?:=(?:[1a\*]))\]|\[\/list\]).+?)|(?R))*)\[\/list\]/ems');
	$replace = array('preparse_list_tag(\'$2\', \'$1\', $errors)');
	$text = preg_replace($pattern, $replace, $text);

	$text = str_replace('*'."\0".']', '*]', $text);
	$text = do_clickable($text);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\0", $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
			$text .= '[code]'.$inside[$i].'[/code]';
		}
	}

	$temp_text = false;
	if (empty($errors))
	$temp_text = preparse_tags($text, $errors, $is_signature);

	if ($temp_text !== false)
	$text = $temp_text;

	// Remove empty tags
	while ($new_text = preg_replace('/\[(b|u|i|h|colou?r|quote|code|img|url|email|list)(?:\=[^\]]*)?\]\[\/\1\]/', '', $text))
	{
		if ($new_text != $text)
		$text = $new_text;
		else
		break;
	}

	return forum_trim($text);
}
endif;

if ( !function_exists('do_clickable') ) :
function do_clickable($text) {
	$text = ' '.$text;

	$text = preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-]?)?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#ie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5://$6\', \'$5://$6\', true).stripslashes(\'$4$10$11$12\')', $text);
	$text = preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#ie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5.$6\', \'$5.$6\', true).stripslashes(\'$4$10$11$12\')', $text);

	return substr($text, 1);
}
endif;

if ( !function_exists('handle_url_tag') ) :
function handle_url_tag($url, $link = '', $bbcode = false)
{
	$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);
	if (strpos($url, 'www.') === 0)                 // If it starts with www, we add http://
	$full_url = 'http://'.$full_url;
	else if (strpos($url, 'ftp.') === 0)    // Else if it starts with ftp, we add ftp://
	$full_url = 'ftp://'.$full_url;
	else if (!preg_match('#^([a-z0-9]{3,6})://#', $url))    // Else if it doesn't start with abcdef://, we add http://
	$full_url = 'http://'.$full_url;

	// Ok, not very pretty :-)
	if (!$bbcode)
	$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' &#133; '.substr($url, -10) : $url) : stripslashes($link);

	if ($bbcode)
	{
		if ($full_url == $link)
		return '[url]'.$link.'[/url]';
		else
		return '[url='.$full_url.']'.$link.'[/url]';
	}
	else
	return '<a href="'.$full_url.'">'.$link.'</a>';
}
endif;

if ( !function_exists('handle_img_tag') ) :
function handle_img_tag($url, $alt = null)
{
	if ($alt == null) {
		$alt = $url;
	}

	$img_tag = '<img src="'.$url.'" alt="'.forum_htmlencode($alt).'" />';

	return $img_tag;
}
endif;

if ( !function_exists('handle_list_tag') ) :
function handle_list_tag($content, $type = '*')
{
	if (strlen($type) != 1)
	$type = '*';

	if (strpos($content,'[list') !== false)
	{
		$pattern = array('/\[list(?:=([1a\*]))?\]((?>(?:(?!\[list(?:=(?:[1a\*]))\]|\[\/list\]).+?)|(?R))*)\[\/list\]/ems');
		$replace = array('handle_list_tag(\'$2\', \'$1\')');
		$content = preg_replace($pattern, $replace, $content);
	}

	$content = preg_replace('#\s*\[\*\](.*?)\[/\*\]\s*#s', '<li>$1</li>', forum_trim($content));

	if ($type == '*')
	$content = '<ul>'.$content.'</ul>';
	else
	if ($type == 'a')
	$content = '<ol class="alpha">'.$content.'</ol>';
	else
	$content = '<ol class="decimal">'.$content.'</ol>';

	return '</p>'.$content.'<p>';
}
endif;

if ( !function_exists('do_bbcode') ) :
function do_bbcode($text) {
	if (strpos($text, '[quote') !== false)
	{
		$text = preg_replace('#\[quote=(&quot;|"|\'|)(.*?)\\1\]#e', '"<strong>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), \'$2\')." said: </strong>"', $text);
		$text = preg_replace('#\[quote\]\s*#', '<blockquote>', $text);
		$text = preg_replace('#\s*\[\/quote\]#S', '</blockquote><hr/>', $text);
	}

	$pattern[] = '/\[list(?:=([1a\*]))?\]((?>(?:(?!\[list(?:=(?:[1a\*]))\]|\[\/list\]).+?)|(?R))*)\[\/list\]/ems';
	$replace[] = 'handle_list_tag(\'$2\', \'$1\')';

	$pattern[] = '#\[b\](.*?)\[/b\]#ms';
	$pattern[] = '#\[i\](.*?)\[/i\]#ms';
	$pattern[] = '#\[u\](.*?)\[/u\]#ms';
	$pattern[] = '#\[colou?r=([a-zA-Z]{3,20}|\#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})](.*?)\[/colou?r\]#ms';
	$pattern[] = '#\[h\](.*?)\[/h\]#ms';

	$replace[] = '<strong>$1</strong>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span class="bbu">$1</span>';
	$replace[] = '<span style="color: $1">$2</span>';
	$replace[] = '</p><h5>$1</h5><p>';

	$pattern[] = '#\[img\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e';
	$pattern[] = '#\[img=([^\[]*?)\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e';
	if ($is_signature)
	{
		$replace[] = 'handle_img_tag(\'$1$3\', true)';
		$replace[] = 'handle_img_tag(\'$2$4\', true, \'$1\')';
	}
	else
	{
		$replace[] = 'handle_img_tag(\'$1$3\', false)';
		$replace[] = 'handle_img_tag(\'$2$4\', false, \'$1\')';
	}

	$pattern[] = '#\[url\]([^\[]*?)\[/url\]#e';
	$pattern[] = '#\[url=([^\[]+?)\](.*?)\[/url\]#e';
	$pattern[] = '#\[email\]([^\[]*?)\[/email\]#';
	$pattern[] = '#\[email=([^\[]+?)\](.*?)\[/email\]#';

	$replace[] = 'handle_url_tag(\'$1\')';
	$replace[] = 'handle_url_tag(\'$1\', \'$2\')';
	$replace[] = '<a href="mailto:$1">$1</a>';
	$replace[] = '<a href="mailto:$1">$2</a>';

	// This thing takes a while! :)
	$text = preg_replace($pattern, $replace, $text);

	return $text;
}
endif;

if ( !function_exists('split_text') ) :
function split_text($text, $start, $end, $retab = true)
{
        $tokens = explode($start, $text);

        $outside[] = $tokens[0];

        $num_tokens = count($tokens);
        for ($i = 1; $i < $num_tokens; ++$i)
        {
                $temp = explode($end, $tokens[$i]);

                if (count($temp) != 2)
                {
                        return array(null, array($text));
                }
                $inside[] = $temp[0];
                $outside[] = $temp[1];
        }

        if ($retab)
        {
                $spaces = str_repeat(' ', 8);
                $inside = str_replace("\t", $spaces, $inside);
        }

        return array($inside, $outside);
}
endif;