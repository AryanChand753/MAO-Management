<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/accounts.php";
safeStartSession();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/snippets.php";
navigationBarAndBootstrap();
stylesheet();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/permissions.php";
checkPerms(OFFICER_PERMS);

// Process upload and redirect to create bubble sheets
$attribute_name = 'bubbleInfoCSV';
if (isset($_FILES[$attribute_name], $_POST['test'])) {
	$errors = array();
	$file_name = $_FILES[$attribute_name]['name'];
	$file_size = $_FILES[$attribute_name]['size'];
	$file_tmp = $_FILES[$attribute_name]['tmp_name'];
	$file_type = $_FILES[$attribute_name]['type'];

	$temp_array = explode('.', $_FILES[$attribute_name]['name']);
	$file_ext = strtolower(end($temp_array));

	if ($file_ext != 'csv')
		die("Error: File uploaded must be a CSV!");

	if (empty($errors)) {
		$test = $_POST['test'];

		$new_filename = md5(rand());
		if (!move_uploaded_file($file_tmp, $_SERVER['DOCUMENT_ROOT'] . "/../uploads/" . $new_filename))
			die("Error moving file to uploads folder.");

		redirect(relativeURL('admin/bubbles/createPDF?ref=' . currentURL(false) . "&test=$test&csv_filename=$new_filename"));
	} else
		print_r($errors);
}

?>

<title>DB | Bubble - Upload File</title>

<h2 style="margin: 6px;"><u>Bubbles From File</u></h2>

<form method="post" action="" enctype="multipart/form-data"
      class="filled border">
    <fieldset>
        <label for="test">Test Name:</label>
        <input id="test" name="test" type="text"><br>
        <br>

        <label for="bubbleInfoCSV"><u>CSV Upload (<?php echo makeLink('Example', 'shared/examples/CSV Example.csv') ?>
                )</u></label>
        <input id="bubbleInfoCSV" name="bubbleInfoCSV" type="file" required><br>

        <input type="submit" value="Create Bubbles!">
    </fieldset>
</form><br>
<br>

<a href="https://github.com/AnirudhRahul/FAMATBubbler" class="rainbow">
    ♥ Credit Where Credit Is Due ♥️</a>
