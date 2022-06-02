<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/accounts.php";
safelyStartSession();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/snippets.php";
stylesheet();

require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/permissions.php";
checkPerms(OFFICER_PERMS);

// Redirect to reference header (after creating PDF)
$ref = "https://" . $_SERVER['HTTP_HOST'];
if (isset($_GET['ref'])) {
	$ref = $_GET['ref'];

	if (!strpos($_GET['ref'], '?'))
		$ref .= "?return=";
	else
		$ref .= "&return=";
}

$json_array = array();
if (isset($_POST['selected']) && is_array($_POST['selected'])) {
	$ref .= "true";

	$IDs = $_POST['selected'];

    $json_array['comp'] = $_POST['comp'];
	$json_array['bubbles'] = array(
		true,   // 1-4: FAMAT ID (unique for each school)
		true,
		true,
		true,
		true,   // 5-7: Unique Student ID (changes if they change school)
		true,
		true,
		true,   // 8: Division
		true);  // 9: Team

	require_once $_SERVER['DOCUMENT_ROOT'] . "/shared/competitions.php";

	$json_array['students'] = array();
	foreach ($IDs as $id) {
		$student = array();

		$famat_id = "";

		$grade = getGrade($id);
		if (6 <= $grade && $grade <= 8) {
			$student['school'] = "Doral Academy Middle School";
			$famat_id .= '5377';
		} else if (9 <= $grade && $grade <= 12) {
			$student['school'] = "Doral Academy High School";
			$famat_id .= '5375';
		} else {    // Invalid grade
			$student['school'] = "Doral Academy __________ School";
			$famat_id .= '537 ';
		}

		$student['name'] = getAccountDetail('people', 'first_name', $id)
			. " "
			. getAccountDetail('people', 'last_name', $id);

		$mu_student_id = getAccountDetail('competitor_info', 'mu_student_id', $id);
		if (preg_match('/[0-9\s]{3}/', $mu_student_id) == 0)
			$mu_student_id = '   ';
		$famat_id .= $mu_student_id;

		$division = getAccountDetail('competitor_info', 'division', $id);
		if (1 <= $division && $division <= 6)
			$famat_id .= $division;
		else    // Invalid division
			$famat_id .= ' ';

		$student['test'] = DIVISIONS[$division] . " Individual";

		// TODO: Implement team selection
		$famat_id .= ' ';   // Team digit

		$student['famat_id'] = $famat_id;

		$json_array['students'][] = $student;
	}
} else if (isset($_GET['csv_filename'], $_GET['comp'])) { // CSV file
	$comp = $_GET['comp'];
	$csv_filename = $_GET['csv_filename'];

	$ref .= "true";

	$json_array['comp'] = $comp;
	$json_array['bubbles'] = array(
		true,   // 1-4: FAMAT ID (unique for each school)
		true,
		true,
		true,
		true,   // 5-7: Unique Student ID (changes if they change school)
		true,
		true,
		true,   // 8: Division
		true);  // 9: Team

	$json_array['students'] = array();
	if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . "/../uploads/$csv_filename.csv", "r")) !== FALSE) {
		fgetcsv($handle); // Skip header
		while (($data = fgetcsv($handle)) !== FALSE) {
			$student = array();

			$student['school'] = "Doral Academy Charter School";

			$student['name'] = $data[1];

            $student['test'] = "_______________ Individual";

			$student['famat_id'] = $data[0];

			$json_array['students'][] = $student;
		}
	} else
		die("ERROR: Could not read CSV file with filename '$csv_filename'!");
} else {
	redirect($ref . 'true');
	die();
}

$json = json_encode($json_array);
?>

<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js'></script>

<!--suppress JSUnresolvedFunction -->
<script>
	createPDF();

	function createPDF() {
		const doc = new jsPDF({
			orientation: 'portrait',
			unit: 'pt',
			format: [611.77, 791.88]
		});
		doc.setFont("helvetica");
		doc.setFontSize(12);
		doc.deletePage(1);  // Start off with no pages (delete blank page?)

		// TODO
		const jsonText = '<?php echo $json; ?>';
		console.log(jsonText);
		const json = JSON.parse(jsonText);

		const comp = json['comp'];
		const bubbles = json['bubbles'];

		const background = new Image;
		background.src = 'blankBubbleSheet.png';

		let student;
		const students = json['students'];
		for (student of students) {
			try {
				doc.addPage();

				const name = student['name'];
				const school = student['school'];
				const test = student['test'];
				const id = student['famat_id'];

				doc.addImage(background, 'PNG', 0, 0, 611.77, 791.88);

				drawPage(doc, name, id, school, test, bubbles);
			} catch (error) {
				console.log("Error (Name = " + student['name'] + "): " + error);
			}
		}

		try {
			doc.save(comp + " - " + (new Date().toLocaleString()) + ".pdf");
		} catch (error) {
			console.log("Saving Error: " + error);
		}

		window.location.replace("<?php echo $ref; ?>");
	}

	function drawPage(pdf, studentName, studentID, school, testName, bubbles) {
		drawName(pdf, studentName);
		drawSchool(pdf, school);
		drawTestName(pdf, testName);
		drawID(pdf, studentID, bubbles);
	}

	function drawName(pdf, name) {
		pdf.text(name, 168, 113);
	}

	function drawSchool(pdf, school) {
		pdf.text(school, 168, 133);
	}

	function drawTestName(pdf, testName) {
		pdf.text(testName, 168, 153);
	}

	function drawID(pdf, id, bubbles) {
		const startX = 106,
			diffX = 17,
			diffY = 15.34,
			offsetY = 19.5,
			offsetX = 4.7;

		const offSets = [0.1, 0, -0.6, -0.9, -0.5, -1, -1.8, -1.8, -1];

		for (let i = 0; i < id.length; i++) {
			if (bubbles[i] && id.charAt(i) !== ' ') {
				const currX = startX + diffX * i;
				pdf.text(id.charAt(i), currX, 211);

				const index = parseInt(id.charAt(i));
				pdf.circle(currX + offsetX + offSets[i], 211 + offsetY + diffY * index, 6.6, 'F');
			}
		}
	}
</script>
