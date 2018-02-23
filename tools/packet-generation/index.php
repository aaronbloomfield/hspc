<?php

$colors = array("dark purple", "yellow", "orange", "pink", "light purple", "dark green", "light blue",
                "red", "dark blue", "light green", "silver", "gold", "silver", "light green");

printHeader();
if ( validPOST() ) {
    archiveOldProblem();
    $name = saveProblem();
    assemblePacket($name);
}
printFooter();

function archiveOldProblem() {
    $pid = preg_replace("/[^A-Za-z0-9]/", '', $_POST['pid']);
    $cmd = "mv problems/$pid* problems/old/";
    system($cmd);
}

function saveProblem() {
    $pid = preg_replace("/[^A-Za-z0-9]/", '', $_POST['pid']);
    $dir = "problems/$pid-" . time();

    mkdir ($dir);

    file_put_contents("$dir/title.tex","\\section{" . $_POST['title'] . "}\n");
    file_put_contents("$dir/title.txt",$_POST['title']);
    file_put_contents("$dir/statement.md",$_POST['problem']);
    file_put_contents("$dir/inputformat.md",$_POST['formalinput']);
    file_put_contents("$dir/outputformat.md",$_POST['formaloutput']);
    file_put_contents("$dir/$pid.sample.in",$_POST['sampleinput']);
    file_put_contents("$dir/$pid.sample.out",$_POST['sampleoutput']);
    if ( $_POST['judginginput'] == "" )
        file_put_contents("$dir/$pid.judging.in",$_POST['sampleinput']);
    else
        file_put_contents("$dir/$pid.judging.in",$_POST['judginginput']);
    if ( $_POST['judgingoutput'] == "" )
        file_put_contents("$dir/$pid.judging.out",$_POST['sampleoutput']);
    else
        file_put_contents("$dir/$pid.judging.out",$_POST['judgingoutput']);
    file_put_contents("$dir/entry.txt",$_POST['difficulty'] . " $pid $dir\n");
    
    for ( $i = 1; $i <= 3; $i++ )
        if ( isset($_FILES["solfile$i"]) && ($_FILES["solfile$i"]!="") )
            system ("cp " . $_FILES["solfile$i"]['tmp_name'] . " $dir/" . $_FILES["solfile$i"]['name']);

    foreach ( array("statement","inputformat","outputformat") as $file )
        system ("pandoc -o $dir/$file.tex $dir/$file.md");

    system ("cat problems/*/entry.txt | sort > problems/entries.txt");
    return $pid;
}

function validPOST() {

    $requiredFields = array(
        "pid" => "Problem ID",
        "title" => "Problem title",
        "difficulty" => "Difficulty level",
        "problem" => "Problem description",
        "formalinput" => "Formal input description",
        "formaloutput" => "Formal output description",
        "sampleinput" => "Sample input",
        "sampleoutput" => "Sample output",
    );

    $errors = array();
    if ( count($_POST) == 0 )
        return false;
    foreach ( $requiredFields as $key => $value )
        if ( !isset($_POST[$key]) || ($_POST[$key] == "") || ($_POST[$key] == "0") )
            $errors[] = $value . " is a required field";
    if ( count($errors) != 0 ) {
        outputErrors($errors);
        return false;
    }

    return true;
}

function outputErrors($errors) {
    if ( count($errors) == 0 )
        return;
    echo "<hr><h3 style='color:red'>Errors encountered:</h3><ol>";
    foreach ($errors as $error)
        echo "<li style='color:red'><b>Error:</b> $error</li>";
    echo "</ol><p style='color:red'><b>NOTE:</b> most form fields have been preserved, but you will have to re-select any file uploads.</p><hr>";
}

function assemblePacket($name) {
    global $colors;
    system ("cp template-packet.tex problems/packet.tex");
    system ("cp template-single.tex problems/$name.tex");
    $fp = fopen("problems/packet.tex","a");
    $fp2 = fopen("problems/$name.tex","a");
    $fptoc = fopen("problems/toc.tex","w");
    $entries = file_get_contents("problems/entries.txt");
    $lines = explode("\n",$entries);
    $c = 0;
    foreach ( $lines as $line ) {
        // avoid trying to parse the end empty line
        if ( trim($line) == "" )
            continue;
        // write the \problem{}{} LaTeX command
        $parts = explode(" ",$line);
        fprintf ($fp,"\\problem{" . $parts[2] . "}{" . $parts[1] . "}\n");
        fprintf ($fp2,"\\problem{" . $parts[2] . "}{" . $parts[1] . "}\n");
        // add the title to the table of contents
        $title = file_get_contents($parts[2] . "/title.txt");
        $color = $colors[$c];
        fprintf ($fptoc, chr(65+$c++) . " & $title & $color \\\\ \\hline\n");
    }
    fprintf ($fp, "\\end{document}\n");
    fprintf ($fp2, "\\end{document}\n");
    fclose($fp);
    fclose($fptoc);

    $ret = runPDFLaTeX("packet",false);
    if ( !$ret )
        return;
    $ret = runPDFLaTeX("packet",false);
    if ( !$ret )
        return;
    $ret = runPDFLaTeX($name,false);
    if ( !$ret )
        return;
    $ret = runPDFLaTeX($name,true);
    if ( !$ret )
        return;
}

function runPDFLaTeX($filenamebase, $outputOnSuccess) {
    $cmd = "cd problems; /usr/bin/pdflatex -halt-on-error $filenamebase.tex";
    $output = array();
    $retval = 0;
    exec($cmd, $output, $retval);
    if ( $retval != 0 )
        echo "<fieldset><legend><b style='color:red'>ERROR!</b></legend>There was an error with creating the problem packet.  See the LaTeX output below for help with what the specific error is.  The submitted form values are in the form below (although you will have to re-select any file uploads).</fieldset><pre>" . implode("\n",$output) . "</pre><hr>";
    else if ( $outputOnSuccess )
        echo "<fieldset><legend><b style='color:darkgreen'>SUCCESS!</b></legend>The problem was submitted successfully.  The input fields below contain the submitted values, in case you want to edit them and re-submit (although you will have to re-select any file uploads).  You can view the PDF of your problem <a href='problems/$filenamebase.pdf'>here</a>.</fieldset>";
    return $retval == 0;
}

function printHeader() {
echo <<<EOT
<!doctype html>
<html>
<head>
  <title>HSPC Packet Creator</title>
  <style>
    table {
      border:1px solid black;
    }
    td {
      border-bottom:1px solid black;
      border-collapse: true;
    }
  </style>
</head>
<body>
<h1>HSPC Problem Packet Generator</h1>
EOT;
}

function printFooter() {
    $pid = isset($_POST['pid']) ? $_POST['pid'] : "";
    $title = isset($_POST['title']) ? $_POST['title'] : "";
    $problem = isset($_POST['problem']) ? $_POST['problem'] : "";
    $formalinput = isset($_POST['formalinput']) ? $_POST['formalinput'] : "";
    $formaloutput = isset($_POST['formaloutput']) ? $_POST['formaloutput'] : "";
    $imageurl = isset($_POST['imageurl']) ? $_POST['imageurl'] : "";
    $sampleinput = isset($_POST['sampleinput']) ? $_POST['sampleinput'] : "";
    $sampleoutput = isset($_POST['sampleoutput']) ? $_POST['sampleoutput'] : "";
    $judginginput = isset($_POST['judginginput']) ? $_POST['judginginput'] : "";
    $judgingoutput = isset($_POST['judgingoutput']) ? $_POST['judgingoutput'] : "";
    $ignorewschecked = (isset($_POST['ignorews']) && ($_POST['ignorews']=="on")) ? "checked" : "";
    $diff = array("selected", "", "", "", "", "", "");
    if ( isset($_POST['difficulty']) ) {
        $diff[0] = "";
        $diff[$_POST['difficulty']] = "selected";
    }
    echo <<<EOT
<p>The problem statement, input format, and output format should be in Markdown, although LaTeX style formulas can be enclosed in dolar signs.  The sample and judging I/O is shown verbatim exactly as it is shown below.</p>

<form method="post" action="packet.php" enctype="multipart/form-data">

  <table>

    <tr><td>Problem ID</td><td><input id="pid" name="pid" type="text" title="Problem ID" value="$pid"></td><td>Required.  A one word informal title of the problem.  This can be anything, as long as it is the same value if you update the form (this is how this form knows it's an edit and not a new submission).</td></tr>

    <tr><td>Problem title</td><td><input id="title" name="title" type="text" title="Problem title" value="$title"></td><td>Required.  The title for the problem.  No ampersands, please.</td></tr>

    <tr><td>Difficulty level</td><td><select id="difficulty" name="difficulty" title="Difficulty level">
	  <option value="0" $diff[0]>0: Unset</option>
	  <option value="1" $diff[1]>1: Low</option>
	  <option value="2" $diff[2]>2: Low to medium</option>
	  <option value="3" $diff[3]>3: Medium</option>
	  <option value="4" $diff[4]>4: Medium to hard</option>
	  <option value="5" $diff[5]>5: Hard</option>
	  <option value="6" $diff[6]>6: Very hard</option>
      </select></td><td>Required. Take your best guess.</td></tr>

    <tr><td>Problem description</td><td><textarea id="problem" name="problem" cols="60" rows="10" title="Problem description">$problem</textarea></td><td>Required.  This is the description of the problem to be solved, and should also include a sentence or paragraph about the plot (if desired).  This should be in Markdown, with LaTeX style formulas enclosed in dolar signs.

    <tr><td>Image</td><td>Via URL: <input id="imageurl" name="imageurl" type="text" title="Image URL" value="$imageurl"> OR<br>Via upload: <input name="imagefile" type="file"></td><td>Optional.  If you have a appropriate image, then include it here -- either as a URL of the image, or as a file upload.</td></tr>
    
    <tr><td>Formal input description</td><td><textarea id="formalinput" name="formalinput" cols="60" rows="10" title="Formal input description" value="$formalinput">$formalinput</textarea></td><td>Required.  This is the formal description of the input.  Recall that the first value should be an integer on its own line, which is the number of input cases -- this prevents the competitors from having to test for end of file.  This should be in Markdown, with LaTeX style formulas enclosed in dolar signs.

    <tr><td>Formal output description</td><td><textarea id="formaloutput" name="formaloutput" cols="60" rows="10" title="Formal output description" value="$formaloutput">$formaloutput</textarea></td><td>Required.  This is the formal output description.  Be sure to make clear how whitespace is being handled, and consider being lenient on said whitespace.  This should be in Markdown, with LaTeX style formulas enclosed in dolar signs.

    <tr><td>Sample input</td><td><textarea id="sampleinput" name="sampleinput" cols="60" rows="10" title="Sample input">$sampleinput</textarea></td><td>Required.  This is the text file that will be provided to the program as input.</td></tr>

    <tr><td>Sample output</td><td><textarea id="sampleoutput" name="sampleoutput" cols="60" rows="10" title="Sample output">$sampleoutput</textarea></td><td>Required.  This is the text file that the output of the program will be compared to (possibly ignoring whitespace) to test for correctness.</td></tr>

    <tr><td>Ignore whitespace?</td><td><input id="ignorews" name="ignorews" type="checkbox" $ignorewschecked></td><td>Select if whitespace should be ignored on the line</td></tr>

    <tr><td>Judging input</td><td><textarea id="judginginput" name="judginginput" cols="60" rows="10" title="Judging input">$judginginput</textarea></td><td>Optional.  If not provided, then the sample input will be used instead.</td></tr>

    <tr><td>Judging output</td><td><textarea id="judgingoutput" name="judgingoutput" cols="60" rows="10" title="Judging output">$judgingoutput</textarea></td><td>Optional.  If not provided, then the sample output will be used instead.</td></tr>

    <tr><td>Sample solutions</td><td><input name="solfile1" type="file"><br><input name="solfile2" type="file"><br><input name="solfile3" type="file"></td><td>Optional.  Sample solutions, if available.  They can be in any language, but should have different names else one will overwrite the other.</td></tr>
    
</table>
<input type="submit" value="Submit form">
</div>
</form>
</body>
</html>
EOT;
}
?>
