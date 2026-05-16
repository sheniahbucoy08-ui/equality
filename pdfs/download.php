<?php
/**
 * EqualVoice — On-the-fly PDF generator for learning resources.
 *
 * Serves real, valid PDF/1.4 documents (no external library) for the
 * Help Desk learning materials. Supports both inline viewing and
 * forced download via ?mode=download.
 *
 * URL examples:
 *   /equalvoice/pdfs/download.php?file=equal-pay-act
 *   /equalvoice/pdfs/download.php?file=equal-pay-act&mode=download
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================================
// Resource catalogue
// ============================================================
$RESOURCES = [
    'equal-pay-act' => [
        'title'    => 'The Equal Pay Act',
        'subtitle' => 'A Guide to Fair Compensation Across Genders',
        'sections' => [
            ['heading' => 'Overview',
             'body'    => "The Equal Pay Act (EPA) of 1963 is a landmark United States labor law that prohibits sex-based wage discrimination between men and women in the same establishment who perform jobs that require substantially equal skill, effort, and responsibility under similar working conditions.\n\nThis legislation was the first federal anti-discrimination law to address wage differences based on gender and laid the groundwork for additional civil rights protections."],

            ['heading' => 'Key Provisions',
             'body'    => "1. Equal pay for equal work regardless of gender.\n2. Wages must be reviewed based on the four factors of skill, effort, responsibility, and working conditions.\n3. Employers cannot reduce the wages of any employee to comply with the Act.\n4. Differentials based on seniority, merit systems, productivity, or any factor other than sex are permitted."],

            ['heading' => 'Who Is Covered',
             'body'    => "Virtually all employers are subject to the Equal Pay Act, including federal, state, and local governments, as well as educational institutions. The Act applies to all forms of compensation including salary, overtime pay, bonuses, stock options, profit sharing, life insurance, vacation pay, and reimbursement for travel."],

            ['heading' => 'How to File a Complaint',
             'body'    => "If you believe your employer is violating the Equal Pay Act you can file a charge with the Equal Employment Opportunity Commission (EEOC). Charges must generally be filed within 180 days of the alleged unlawful practice. Some states extend this window to 300 days when state law also covers the violation."],

            ['heading' => 'Action Steps for Employees',
             'body'    => "- Know your rights and document your work.\n- Research salary ranges for your role and industry.\n- Discuss compensation transparently with peers when comfortable.\n- Request written justification for pay differentials.\n- Consult HR or legal counsel if you suspect discrimination.\n- File a formal charge through the EEOC if internal channels fail."],

            ['heading' => 'Resources',
             'body'    => "U.S. Equal Employment Opportunity Commission: www.eeoc.gov\nDepartment of Labor: www.dol.gov\nNational Women's Law Center: www.nwlc.org\nInstitute for Women's Policy Research: www.iwpr.org"],
        ],
    ],

    'anti-discrimination-laws' => [
        'title'    => 'Anti-Discrimination Laws',
        'subtitle' => 'Protections Against Workplace Discrimination',
        'sections' => [
            ['heading' => 'Introduction',
             'body'    => "A complex web of federal, state, and local statutes protects employees from discrimination based on protected characteristics. This guide summarizes the most important laws every worker should understand and outlines how to seek recourse when those rights are violated."],

            ['heading' => 'Title VII of the Civil Rights Act',
             'body'    => "Title VII prohibits employment discrimination based on race, color, religion, sex, and national origin. The Supreme Court's 2020 Bostock decision extended Title VII protection to discrimination based on sexual orientation and gender identity, ensuring that LGBTQ+ workers are covered."],

            ['heading' => 'Other Key Federal Laws',
             'body'    => "- Age Discrimination in Employment Act (ADEA): protects workers 40 and older.\n- Americans with Disabilities Act (ADA): protects qualified individuals with disabilities.\n- Pregnancy Discrimination Act (PDA): prohibits discrimination based on pregnancy, childbirth, or related conditions.\n- Genetic Information Nondiscrimination Act (GINA): prohibits use of genetic information in employment decisions.\n- Equal Pay Act (EPA): mandates equal pay for substantially equal work."],

            ['heading' => 'Protected Classes',
             'body'    => "Federally protected classes include race, color, religion, sex (including pregnancy, sexual orientation, and gender identity), national origin, age, disability, and genetic information. Many states and municipalities expand this list to include marital status, parental status, military service, political affiliation, and more."],

            ['heading' => 'Recognizing Discrimination',
             'body'    => "Discrimination is not always overt. Watch for patterns such as: being passed over for promotion despite strong performance, exclusion from key meetings or assignments, disparate disciplinary treatment, hostile or harassing comments, retaliation for raising concerns, or systemic policies that disadvantage a protected group."],

            ['heading' => 'Reporting and Enforcement',
             'body'    => "1. Document incidents in writing including dates, witnesses, and exact statements.\n2. Use internal HR or ethics channels first when safe to do so.\n3. File a charge with the EEOC within 180 to 300 days depending on jurisdiction.\n4. Consult an employment attorney for guidance on next steps.\n5. Retaliation for reporting discrimination is itself unlawful."],

            ['heading' => 'Closing Note',
             'body'    => "Knowledge is power. Understanding your rights is the first step in creating a fair and inclusive workplace where every employee can thrive regardless of who they are."],
        ],
    ],

    'inclusive-hiring-guide' => [
        'title'    => 'Inclusive Hiring Guide',
        'subtitle' => 'Best Practices for Equitable Recruitment',
        'sections' => [
            ['heading' => 'Why Inclusive Hiring Matters',
             'body'    => "Diverse teams outperform homogeneous ones in innovation, problem-solving, and financial performance. Inclusive hiring is not a checkbox exercise; it is a strategic imperative that strengthens organizations while opening doors that have historically been closed to underrepresented groups."],

            ['heading' => 'Crafting an Inclusive Job Posting',
             'body'    => "- Use gender-neutral language and avoid coded words like rockstar, ninja, or aggressive.\n- List only must-have qualifications; women and minorities often self-select out when they do not meet every preferred requirement.\n- Highlight commitment to diversity, equity, and inclusion in the company description.\n- Include salary ranges to promote pay transparency.\n- Mention flexibility, parental leave, and accessibility accommodations."],

            ['heading' => 'Sourcing a Diverse Pipeline',
             'body'    => "Cast a wider net by partnering with organizations that serve underrepresented communities such as Women in Technology International, Out in Tech, the Society of Hispanic Professional Engineers, and the National Society of Black Engineers. Attend conferences and career fairs that target diverse talent and post on niche job boards."],

            ['heading' => 'Structured Interviews',
             'body'    => "Structured interviews reduce bias and improve hiring outcomes:\n1. Define competencies before interviewing.\n2. Ask every candidate the same questions in the same order.\n3. Use a scoring rubric tied to job requirements.\n4. Conduct panel interviews with diverse interviewers.\n5. Avoid culture-fit questions; assess for culture-add instead."],

            ['heading' => 'Mitigating Unconscious Bias',
             'body'    => "Common biases that creep into hiring include affinity bias, halo and horns effects, confirmation bias, and the just-like-me effect. Counteract them through bias training, blinded resume screening, structured scoring, and routine audits of demographic outcomes at each stage of the funnel."],

            ['heading' => 'Measuring Success',
             'body'    => "Track quantitative metrics such as application diversity, pass-through rates by demographic, time-to-hire, offer acceptance rates, and 90-day retention. Pair them with qualitative measures such as candidate experience surveys and interviewer feedback to drive continuous improvement."],

            ['heading' => 'Onboarding for Inclusion',
             'body'    => "Hiring is only the start. Pair new hires with mentors, encourage Employee Resource Group (ERG) participation, set clear 30/60/90 day goals, schedule regular check-ins, and ensure access to development opportunities so every new team member can succeed."],
        ],
    ],

    'gender-diversity-toolkit' => [
        'title'    => 'Gender Diversity Toolkit',
        'subtitle' => 'Building Inclusive Workplace Cultures',
        'sections' => [
            ['heading' => 'Introduction',
             'body'    => "This toolkit provides practical strategies for organizations committed to advancing gender diversity. It covers assessment, policy design, training, allyship, and accountability — the building blocks of a workplace where everyone can thrive."],

            ['heading' => 'Step 1: Assess the Current State',
             'body'    => "Begin with a transparent baseline. Analyze representation across levels, departments, and functions. Examine pay equity, promotion velocity, and attrition by gender. Conduct anonymous engagement and inclusion surveys, then share the findings honestly with leadership and employees alike."],

            ['heading' => 'Step 2: Set Public Goals',
             'body'    => "Goals you do not measure rarely move. Establish specific, time-bound targets for representation at each level, leadership pipelines, pay equity adjustments, and inclusion survey scores. Tie executive compensation to progress and report results publicly each year."],

            ['heading' => 'Step 3: Build Inclusive Policies',
             'body'    => "- Parental leave that is generous and gender-neutral.\n- Flexible and remote work options.\n- Comprehensive health benefits including reproductive and trans-affirming care.\n- Domestic partner benefits.\n- Anti-harassment policies with clear reporting channels and protection from retaliation.\n- Bathroom and dress-code policies that respect gender identity."],

            ['heading' => 'Step 4: Train Continuously',
             'body'    => "One-off training is rarely effective. Embed ongoing education on unconscious bias, inclusive leadership, allyship, and harassment prevention. Train managers on equitable performance reviews and feedback. Provide all employees with safe spaces to discuss difficult topics."],

            ['heading' => 'Step 5: Empower Employee Resource Groups',
             'body'    => "ERGs accelerate culture change when they are properly resourced. Provide budget, executive sponsorship, and time during work hours. Compensate ERG leaders for their work. Use ERGs as advisory bodies on policy, product, and people decisions."],

            ['heading' => 'Step 6: Cultivate Allies',
             'body'    => "Allies are essential. Encourage advocacy through these behaviors: amplifying underrepresented voices in meetings, sponsoring (not just mentoring) diverse talent, speaking up against bias, sharing privilege, asking and using correct pronouns, and being an active listener."],

            ['heading' => 'Step 7: Hold Leadership Accountable',
             'body'    => "Leadership commitment determines outcomes. Tie diversity metrics to executive scorecards. Audit hiring, promotion, and compensation decisions for bias. Celebrate progress publicly. Address regression honestly. Remember: culture is built by what leaders tolerate as much as what they champion."],

            ['heading' => 'Closing',
             'body'    => "Building an inclusive culture is a marathon, not a sprint. Progress is rarely linear and setbacks are common. Stay committed, listen continuously, and keep moving forward. Every voice matters; every gender deserves to lead."],
        ],
    ],
];

// ============================================================
// Validation
// ============================================================
$file = $_GET['file'] ?? '';
$mode = ($_GET['mode'] ?? 'inline') === 'download' ? 'attachment' : 'inline';

if (!isset($RESOURCES[$file])) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>PDF Not Found</h1><p>The requested resource does not exist.</p>';
    exit;
}

$data = $RESOURCES[$file];

// ============================================================
// PDF builder
// ============================================================
function pdfEscape(string $s): string {
    // Strip non-Latin1 characters and escape PDF reserved chars.
    $s = mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    return strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
}

/**
 * Build a multi-page PDF/1.4 from a title, subtitle, and array of
 * sections (each with heading + body). Returns the raw PDF byte string.
 */
function buildPdf(string $title, string $subtitle, array $sections): string {
    $pageWidth   = 612;
    $pageHeight  = 792;
    $marginX     = 60;
    $marginTop   = 60;
    $marginBot   = 60;
    $maxLineLen  = 92;
    $usableHeight= $pageHeight - $marginTop - $marginBot;

    // ----- Generate page content streams -----
    $pages   = [];
    $stream  = '';
    $y       = $pageHeight - $marginTop;
    $isFirst = true;

    $newPage = function() use (&$pages, &$stream, &$y, $pageHeight, $marginTop) {
        if ($stream !== '') $pages[] = $stream;
        $stream = '';
        $y = $pageHeight - $marginTop;
    };

    // Title block (only on page 1)
    $stream .= "BT /F2 26 Tf 0.40 0.32 0.62 rg $marginX $y Td (" . pdfEscape($title) . ") Tj ET\n";
    $y -= 32;
    $stream .= "BT /F1 13 Tf 0.36 0.32 0.44 rg $marginX $y Td (" . pdfEscape($subtitle) . ") Tj ET\n";
    $y -= 12;
    // Decorative pride-pastel underline (lavender)
    $stream .= "0.706 0.659 0.878 RG 2 w\n$marginX $y m " . ($pageWidth - $marginX) . " $y l S\n";
    $y -= 24;

    // Sections
    foreach ($sections as $sec) {
        // New page if heading would not fit
        if ($y < $marginBot + 60) { $newPage(); }

        // Heading
        $stream .= "BT /F2 14 Tf 0.40 0.32 0.62 rg $marginX $y Td (" . pdfEscape($sec['heading']) . ") Tj ET\n";
        $y -= 22;

        // Body paragraphs
        $body = str_replace(["\r\n", "\r"], "\n", $sec['body']);
        foreach (explode("\n", $body) as $para) {
            if (trim($para) === '') { $y -= 8; continue; }
            $wrapped = wordwrap($para, $maxLineLen, "\n", true);
            foreach (explode("\n", $wrapped) as $line) {
                if ($y < $marginBot + 14) { $newPage(); }
                $stream .= "BT /F1 11 Tf 0.18 0.15 0.25 rg $marginX $y Td (" . pdfEscape($line) . ") Tj ET\n";
                $y -= 14;
            }
            $y -= 4; // paragraph spacing
        }
        $y -= 14; // section spacing
    }

    // Footer on every page (we'll inject after splitting)
    $pages[] = $stream;

    // Build per-page final stream with footer
    $finalStreams = [];
    $totalPages = count($pages);
    foreach ($pages as $idx => $body) {
        $pageNum = $idx + 1;
        $footer  = "BT /F1 9 Tf 0.55 0.51 0.63 rg $marginX 36 Td (EqualVoice  -  Empowering equality in leadership) Tj ET\n";
        $footer .= "BT /F1 9 Tf 0.55 0.51 0.63 rg " . ($pageWidth - $marginX - 50) . " 36 Td (Page $pageNum of $totalPages) Tj ET\n";
        $finalStreams[] = $body . $footer;
    }

    // ----- Assemble PDF objects -----
    // Object IDs:
    //  1 = Catalog
    //  2 = Pages
    //  3..3+N-1 = Page objects
    //  3+N..3+2N-1 = Page content streams
    //  3+2N = Font F1 (Helvetica)
    //  3+2N+1 = Font F2 (Helvetica-Bold)

    $N = $totalPages;
    $kids = [];
    for ($i = 0; $i < $N; $i++) $kids[] = (3 + $i) . ' 0 R';
    $kidsStr = implode(' ', $kids);

    $fontF1Id = 3 + 2 * $N;
    $fontF2Id = 3 + 2 * $N + 1;

    $objs = [];
    $objs[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objs[2] = "<< /Type /Pages /Kids [$kidsStr] /Count $N >>";

    for ($i = 0; $i < $N; $i++) {
        $pageId    = 3 + $i;
        $contentId = 3 + $N + $i;
        $objs[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageWidth $pageHeight] "
                       . "/Contents $contentId 0 R "
                       . "/Resources << /Font << /F1 $fontF1Id 0 R /F2 $fontF2Id 0 R >> >> >>";
        $stream = $finalStreams[$i];
        $objs[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
    }

    $objs[$fontF1Id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
    $objs[$fontF2Id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

    ksort($objs);

    // ----- Serialize with xref table -----
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0 => 0];
    foreach ($objs as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "$id 0 obj\n$body\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $size = max(array_keys($objs)) + 1;
    $pdf .= "xref\n0 $size\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $size; $i++) {
        $off = $offsets[$i] ?? 0;
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }
    $pdf .= "trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$xrefStart\n%%EOF";

    return $pdf;
}

// ============================================================
// Output
// ============================================================
$pdf = buildPdf($data['title'], $data['subtitle'], $data['sections']);

$filename = preg_replace('/[^a-z0-9\-]/', '', $file) . '.pdf';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/pdf');
header("Content-Disposition: $mode; filename=\"$filename\"");
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=600');
header('X-Content-Type-Options: nosniff');
echo $pdf;
exit;
