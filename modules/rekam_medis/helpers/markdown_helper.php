<?php
/**
 * Simple Markdown to HTML converter (improved)
 * - Lists (UL/OL) are grouped properly and aligned flush-left
 * - Paragraphs have zero margins; no extra spaces before/after
 * - Avoid global <br> so lists don't get broken
 */

function markdownToHtml($input) {
    if ($input === null) return '';

    // Normalize newlines and trim surrounding whitespace
    $text = preg_replace("/\r\n?|\r/", "\n", (string)$input);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    $text = trim($text);

    $lines = explode("\n", $text);

    $html = '';
    $inList = false;
    $listType = null; // 'ul' or 'ol'
    $olCounter = 0;   // track ordered list item count to resume numbering
    $paraBuffer = [];

    $flushParagraph = function() use (&$html, &$paraBuffer) {
        if (!empty($paraBuffer)) {
            $content = trim(implode(" ", $paraBuffer));
            if ($content !== '') {
                // Inline formatting inside paragraph
                $content = applyInlineMarkdown($content);
                $html .= '<p style="margin:0;">' . $content . '</p>';
            }
            $paraBuffer = [];
        }
    };

    $closeList = function() use (&$html, &$inList, &$listType) {
        if ($inList) {
            $html .= ($listType === 'ul') ? '</ul>' : '</ol>';
            $inList = false;
            $listType = null;
        }
    };

    foreach ($lines as $rawLine) {
        $line = rtrim($rawLine);
        if ($line === '') {
            // Blank line: end paragraph only; keep list open to allow continuous numbering
            $flushParagraph();
            // Do NOT close list here; next line may still be a list item
            continue;
        }

        // Headers: # .. ######
        if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
            $flushParagraph();
            $closeList();
            $level = strlen($m[1]);
            $content = applyInlineMarkdown(trim($m[2]));
            $html .= '<h' . $level . ' style="margin:0;">' . $content . '</h' . $level . '>';
            continue;
        }

        // Ordered list: 1. item
        if (preg_match('/^\s*(\d+)\.\s+(.*)$/', $line, $m)) {
            $flushParagraph();
            $markerContent = applyInlineMarkdown(trim($m[2]));
            if (!$inList || $listType !== 'ol') {
                $closeList();
                $inList = true;
                $listType = 'ol';
                // If Markdown uses all '1.' items, we still want continuous numbering across interruptions
                // Use start attribute to resume numbering
                $startAttr = ($olCounter > 0) ? ' start="' . ($olCounter + 1) . '"' : '';
                $html .= '<ol' . $startAttr . ' style="margin:0; padding-left:0; list-style-position: inside;">';
            }
            $olCounter++;
            // Force numbering using value attribute to support TCPDF rendering
            $html .= '<li value="' . $olCounter . '" style="margin:0; padding:0;">' . $markerContent . '</li>';
            continue;
        }

        // Unordered list: -, *, + item
        if (preg_match('/^\s*([\-*+])\s+(.*)$/', $line, $m)) {
            $flushParagraph();
            $markerContent = applyInlineMarkdown(trim($m[2]));
            if (!$inList || $listType !== 'ul') {
                $closeList();
                $inList = true;
                $listType = 'ul';
                $html .= '<ul style="margin:0; padding-left:0; list-style-position: inside;">';
            }
            $html .= '<li style="margin:0; padding:0;">' . $markerContent . '</li>';
            continue;
        }

        // Normal paragraph line (accumulate)
        // If we are currently inside a list, close it before starting paragraph content
        if ($inList) {
            $closeList();
        }
        $paraBuffer[] = $line;
    }

    // Flush remaining paragraph or list
    $flushParagraph();
    $closeList();

    return $html;
}

// Apply inline markdown formatting (bold, italic, code, links)
function applyInlineMarkdown($text) {
    // Code (inline)
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    // Italic (avoid impacting bold syntax already processed)
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/', '<em>$1</em>', $text);
    // Links [text](url)
    $text = preg_replace('/\[(.+?)\]\((https?:[^\)\s]+)\)/', '<a href="$2">$1</a>', $text);
    return $text;
}
?>
