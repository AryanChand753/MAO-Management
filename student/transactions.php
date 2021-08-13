<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/snippets.php";
stylesheet();
navigationBar();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/permissions.php";
checkPerms(STUDENT);

//DEBUG
//echo "SESSION<br>";
//foreach ($_SESSION as $key => $value) {
//	echo "Key: $key; Value: $value<br>";
//}
//echo "<br>", "POST<br>";
//foreach ($_POST as $key => $value) {
//	echo "Key: $key; Value: $value<br>";
//}

// Update process
$updated = null;
if (isset($_POST['transaction'])) {  // Process POST update
	if ($_POST['id'] != $_SESSION['id'] && !checkCompareRank($_SESSION['id'], $_POST['id'], true))   // Confirm rank is higher (so that people can't update through POST requests without being logged into an account of higher rank)
		die("<p style=\"color:red;\">You do not have the required permissions!</p>\n");

	require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/transactions.php";

	$updated = toggleTransaction($_POST['id'], $_POST['transaction']);
}

// View form (using correct ID)
$id = $_SESSION['id'];
if (isset($_GET['id'])) {
	$rankComp = checkCompareRank($_SESSION['id'], $_GET['id'], true);

	if (!is_null($rankComp)) {
		if ($rankComp)
			$id = $_GET['id'];
		else
			die("<p style=\"color:red;\">You do not have the required permissions!</p>\n");
	}
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/SQL.php";
?>

<title>DB | Transactions</title>

<body style="text-align: center;">

<h2 style="margin: 6px;"><u>Transactions</u></h2>

<?php
if (getRank($_SESSION['id']) > 0) {
    getPersonSelect();

	if ($id != $_SESSION['id'])
		echo "<p style=\"color:violet;\"><i><b>Note:</b> You are updating an account that isn't yours, and has a permission rank below you!</i></p>\n";
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/SQL.php";
?>

<form style="margin: 6px;">
    <fieldset>
        <legend><i>Account Information</i>&nbsp
            <div class="tooltip"><i class="fa fa-question-circle"></i>
                <span class="tooltip-text">Edit this information in update info.</span>
            </div>
        </legend>

        <label for="id">ID:</label>
        <input id="id" name="id" type="search" pattern="[0-9]{7}" size="7" value="<?php echo $id; ?>" disabled><br>
        <br>

        <label for="first_name">First Name:</label>
        <input id="first_name" name="first_name" type="text" size="10"
               value="<?php echo getAccountDetail('people', 'first_name', $id); ?>"
               disabled><br>
        <br>

        <label for="last_name">Last Name:</label>
        <input id="last_name" name="last_name" type="text" size="10"
               value="<?php echo getAccountDetail('people', 'last_name', $id); ?>"
               disabled><br>
        <br>

        <label for="grade">Grade:</label>
        <select id="grade" name="grade" disabled> <!-- TODO: Form colors (uniformity) -->
            <option disabled></option>
            <option value="6" <?php echo getGrade($id) == 6 ? "selected" : ""; ?>>6th Grade
            </option>
            <option value="7" <?php echo getGrade($id) == 7 ? "selected" : ""; ?>>7th Grade
            </option>
            <option value="8" <?php echo getGrade($id) == 8 ? "selected" : ""; ?>>8th Grade
            </option>
            <option value="9" <?php echo getGrade($id) == 9 ? "selected" : ""; ?>>9th Grade
            </option>
            <option value="10" <?php echo getGrade($id) == 10 ? "selected" : ""; ?>>10th Grade
            </option>
            <option value="11" <?php echo getGrade($id) == 11 ? "selected" : ""; ?>>11th Grade
            </option>
            <option value="12" <?php echo getGrade($id) == 12 ? "selected" : ""; ?>>12th Grade
            </option>
            <option value="0" <?php echo getGrade($id) == 0 ? "selected" : ""; ?>>Not a
                Student
            </option>
        </select>
    </fieldset>
</form>

<?php
// Report if update was successful
if (isset($updated)) {
	echo $updated ?
		"<p style=\"color:green;\">Successfully updated payment (ID Updated = " . $_POST['id'] . ").</p>\n" :
		"<p style=\"color:red;\">Failed to update payment (ID = " . $_POST['id'] . ").</p>\n";
}

$sql_conn = getDBConn();
if (getRank($_SESSION['id']) > 0) {
//TODO: Better tables; no function will do! Function should ONLY be for custom reports!
	if (!is_a($payment_stmt = $sql_conn->query("SELECT pd.payment_id, pd.cost, pd.info, tr.time_paid FROM payment_details pd LEFT OUTER JOIN transactions tr ON pd.payment_id = tr.payment_id AND id = $id ORDER BY ISNULL(tr.time_paid), tr.time_paid, pd.payment_id;"), 'mysqli_result'))
		die("<p style=\"color:red;\">Get table function occurred an error upon execution of statement!</p>\n");

	require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/transactions.php";

	$table_rows = sql_TH(array_merge($payment_stmt->fetch_fields(), array('paid')));
	while (!is_null($row_array = $payment_stmt->fetch_row())) {
		$table_rows .= TR(array_merge($row_array,
				array(
					"\n<form id='$row_array[0]' method='post'>
                        <input id='id' name='id' type='hidden' value='$id'>
                        <input id='transaction' name='transaction' type='hidden' value='$row_array[0]'>
                        <input id='onchange' name='onchange' type='checkbox' onchange='document.getElementById(\"$row_array[0]\").submit()' " . (isPaid($id, $row_array[0]) ? "checked" : "") . ">
                    </form>")),
				true) . "\n";
	}

	echo surrTags('table', $table_rows, "class='center' style='margin-top: 6px; margin-bottom: 6px;'");
} else {
	$result = $sql_conn->query("SELECT pd.payment_id, pd.info, tr.time_paid FROM payment_details pd LEFT OUTER JOIN transactions tr ON pd.payment_id = tr.payment_id AND id = $id ORDER BY ISNULL(tr.time_paid), tr.time_paid, pd.payment_id;");

	echo getTableFromResult($result) . "\n";
}
?>
