<?php


/*

**** This function is responsible for replacing latex with appropriate HTML tags.

** Parameters :
	$text: abstract or title. 
	$remove: boolean variable to remove the tags completely.

*/

//--------------------------------------------------------------------------------------------

function convertLatexToHTML($text) {
	// Convert LaTeX within $...$ to appropriate HTML
	return preg_replace_callback('/\$(.*?)\$/', function ($matches) {
		$latex = $matches[1];

		$originalLatex = $latex; // Keep the original in case no match is found

		// Convert LaTeX italics (\textit{}) to HTML <i>
		$latex = preg_replace('/\\\\textit\{([^\}]+)\}/', '<i>$1</i>', $latex);

		// Convert LaTeX subscript (_) to HTML <sub>
		// This handles cases like VO$_{2}$
		$latex = preg_replace('/_\\{([^\}]+)\\}/', '<sub>$1</sub>', $latex);

		// This handles cases like CO$_2$
		$latex = preg_replace('/_([^\}]+)/', '<sub>$1</sub>', $latex);

		// Convert LaTeX superscript (^) to HTML <sup>
		// This handles cases like e.g., x$^{2}$
		$latex = preg_replace('/\\^\\{([^\}]+)\\}/', '<sup>$1</sup>', $latex);

		// This handles cases like e.g., x$^2$
		$latex = preg_replace('/\\^([^\}]+)/', '<sup>$1</sup>', $latex);

		 // Check if any conversion happened, if not, return the original LaTeX
		 if ($latex === $originalLatex) {
            return '$' . $originalLatex . '$';
        }

        // Remove any remaining backslashes
        $latex = str_replace('\\', '', $latex);

        return $latex;  // Return the converted or original LaTeX part
		
	}, $text);
}