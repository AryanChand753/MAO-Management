<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/accounts.php";
startSession();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/snippets.php";
navigationBar();
stylesheet();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/permissions.php";
checkPerms(OFFICER);

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/sql.php";

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/competitions.php";

// Get competition name if sent and is valid
$comp = null;
if (isset($_GET['comp_name'])) { // Check if `comp_id` was sent
	// Check if sent `comp_id` is valid
	if (!is_null(getDetail('competitions', 'competition_name', 'competition_name', $_GET['comp_name'])))
		$comp = $_GET['comp_name'];
}

if (is_null($comp))
	die("Competition name not specified.");

// Add student if is sent
$add_id = getSelectID('POST');
if (!is_null($add_id))
	addToComp($comp, $add_id);

$pay_id = getDetail('competitions', 'payment_id', 'competition_name', $comp);
?>

    <style>
        td {
            border: none;
        }
    </style>

    <title>DB | Selections [<?php echo $comp ?>]</title>

    <div class="no-print">
        <h2><u><?php echo $comp; ?> - Selections</u></h2>

        &nbsp;<button type="button" onClick="window.print()" class="no-print">Print</button>
    </div>

<?php
$sql_conn = getDBConn();
$approved_IDs_stmt = $sql_conn->prepare(
	"SELECT
                cd.unique_id,
	            cs.id,
                p.last_name,
                p.first_name,
                ci.division
            FROM competition_selections cs
            LEFT JOIN people p ON p.id = cs.id
            LEFT JOIN competitor_info ci ON ci.id = cs.id
            LEFT JOIN competition_data cd ON cd.id = cs.id AND cd.competition_name = cs.competition_name
            WHERE cs.competition_name = ?
            ORDER BY last_name, first_name");
$approved_IDs_stmt->bind_param('s', $comp);
$approved_IDs_stmt->bind_result($unique_id, $id, $last_name, $first_name, $division);
$approved_IDs_stmt->execute();

const DIVISIONS = array(
	'Not A Student',
	'Algebra I',
	'Geometry',
	'Algebra 2',
	'Precalculus',
	'Calculus',
	'Statistics');

$person = '';
$table_num = 0;
$page = "";
while (!is_null($person)) {
//    echo "ITT";
	$table_header_row =
        "<tr><th colspan='6'>$comp</th></tr>
        <tr>
            <th>Add</th>
            <th>ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Grade</th>
            <th>Division</th>
        </tr>";

	$i = 0;
	$table = "";
	while (++$i < 50 && !is_null($person = $approved_IDs_stmt->fetch())) {
		if ($i == 1)
			$table = $table_header_row;

		// Table data
        $add_cell = '';
        if (is_null($unique_id)) {
	        $add_cell =
		        "<form method='post'>
                    <input type='hidden' name='select-id' value='$id'>
                    <input type='submit' value='Add'>
                </form>";
        }
        $row_interior = surrTags('td', $add_cell, "style='padding: 1 2px;'");

        $row_interior .= surrTags('td', $id, "style='padding: 1 2px;'");

        $row_interior .= surrTags('td', $last_name, "style='text-align: left; padding: 1 2px;'");

        $row_interior .= surrTags('td', $first_name, "style='text-align: left; padding: 1 2px;'");

        $row_interior .= surrTags('td', formatOrdinalNumber(getGrade($id)));

        $row_interior .= surrTags('td', DIVISIONS[$division], "style='padding: 1 2px;'");

        // Define form then add table row (wrap row interior by table row)
        $row = surrTags('tr', $row_interior);

        $table .= $row;
    }

	$page .= surrTags('table', $table, "class='filled' style='font-size: small; display: inline-block; vertical-align: top;'");

	if (++$table_num % 2 == 0 || is_null($person)) {
		echo $page, '<br>';

		$page = "";
	} else
		$page .= '&nbsp;';
}
