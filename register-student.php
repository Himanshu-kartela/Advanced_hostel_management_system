<?php
// Database connection settings
$host = 'localhost'; // Database host
$dbname = 'hostel'; // Database name
$user = 'root'; // Database username
$pass = ''; // Database password

// Create a new PDO instance
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();    
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

if (isset($_POST['submit']))
{
    // Retrieve form data
    $full_name = $_POST['full-name'];
    $contact_no = $_POST['contact'];
    $parents_contact_no = $_POST['parents-contact'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $birthday_date = $_POST['bday'];
    $hobbies = $_POST['hobbies'];
    $extra_skills = $_POST['skills'];
    $english_proficiency = $_POST['english'];
    $blood_group = $_POST['blood-group'];
    $mediclaim = $_POST['mediclaim'];
    $college_name = $_POST['college-name'];
    $study_with_year = $_POST['study'];
    $tenth_percentage = $_POST['10th-percentage'];
    $twelfth_percentage = $_POST['12th-percentage'];
    $photo = $_FILES['photo']['name'];

    $password = md5($password);

    // File upload handling
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($_FILES["photo"]["size"] > 1000000) {
        die("Sorry, your file is too large.");
    }

    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        die("Sorry, only JPG, JPEG, & PNG files are allowed.");
    }

    if ($uploadOk == 0) {
        die("Sorry, your file was not uploaded.");
    } else {
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            // File uploaded successfully
        } else {
            die("Sorry, there was an error uploading your file.");
        }
    }

    try {
        $sql = "INSERT INTO registrations (full_name, contact_no, parents_contact_no, email, password, birthday_date, hobbies, extra_skills, english_proficiency, blood_group, mediclaim, college_name, study_with_year, tenth_percentage, twelfth_percentage, photo)
                VALUES (:full_name, :contact_no, :parents_contact_no, :email, :password, :birthday_date, :hobbies, :extra_skills, :english_proficiency, :blood_group, :mediclaim, :college_name, :study_with_year, :tenth_percentage, :twelfth_percentage, :photo)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':contact_no' => $contact_no,
            ':parents_contact_no' => $parents_contact_no,
            ':email' => $email,
            ':password' => $password,
            ':birthday_date' => $birthday_date,
            ':hobbies' => $hobbies,
            ':extra_skills' => $extra_skills,
            ':english_proficiency' => $english_proficiency,
            ':blood_group' => $blood_group,
            ':mediclaim' => $mediclaim,
            ':college_name' => $college_name,
            ':study_with_year' => $study_with_year,
            ':tenth_percentage' => $tenth_percentage,
            ':twelfth_percentage' => $twelfth_percentage,
            ':photo' => $photo
        ]);

        $registration_id = $user_id;

        try {
            $name_parts = explode(' ', $full_name);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[2]) ? $name_parts[2] : '';
            $middle_name = isset($name_parts[1]) ? $name_parts[1] : '';

            $sql_user = "INSERT INTO userregistration (firstname, middlename, lastname,gender, contactNo, email, password)
                         VALUES (:firstname, :middlename, :lastname,:gen, :contact, :email, :password)";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([
                ':firstname' => $first_name,
                ':middlename' => $middle_name,
                ':lastname' => $last_name,
                ':gen'=>'Male',
                ':contact' => $contact_no,
                ':email' => $email,
                ':password' => $password
            ]);

            // Handle SPI data
            function insertSpiData($pdo, $table, $registration_id, $spi_data) {
                $sql = "INSERT INTO $table (registration_id, semester_number, spi) VALUES (:registration_id, :semester_number, :spi)";
                $stmt = $pdo->prepare($sql);

                foreach ($spi_data as $semester => $spi) {
                    try {
                        $stmt->execute([
                            ':registration_id' => $registration_id,
                            ':semester_number' => $semester,
                            ':spi' => $spi
                        ]);
                    } catch (PDOException $e) {
                        echo "Error: " . $e->getMessage();
                    }
                }
            }

            if (isset($_POST['Dsem'])) {
                $diploma_semesters = intval($_POST['Dsem']);
                $diploma_spi = [];
                for ($i = 1; $i <= $diploma_semesters; $i++) {
                    $spi = isset($_POST["diploma-spi-sem$i"]) ? $_POST["diploma-spi-sem$i"] : null;
                    $diploma_spi[$i] = $spi;
                }
                insertSpiData($pdo, 'diploma_spi', $registration_id, $diploma_spi);
            }

            if (isset($_POST['Bsem'])) {
                $bachelor_semesters = intval($_POST['Bsem']);
                $bachelor_spi = [];
                for ($i = 1; $i <= $bachelor_semesters; $i++) {
                    $spi = isset($_POST["bachelor-spi-sem$i"]) ? $_POST["bachelor-spi-sem$i"] : null;
                    $bachelor_spi[$i] = $spi;
                }
                insertSpiData($pdo, 'bachelor_spi', $registration_id, $bachelor_spi);
            }

            if (isset($_POST['Msem'])) {
                $master_semesters = intval($_POST['Msem']);
                $master_spi = [];
                for ($i = 1; $i <= $master_semesters; $i++) {
                    $spi = isset($_POST["master-spi-sem$i"]) ? $_POST["master-spi-sem$i"] : null;
                    $master_spi[$i] = $spi;
                }
                insertSpiData($pdo, 'master_spi', $registration_id, $master_spi);
            }

            // Output JavaScript for SweetAlert
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Registration completed successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'index.php'; // Redirect if necessary
                        }
                    });
                  </script>";
        } catch (PDOException $e) {
            die("Error inserting into userregistration: " . $e->getMessage());
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.js"></script>
</head>
<body>
<section class="bg-gray-50 dark:bg-gray-900">
  <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
      <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
          <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
              <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                  Create an account
              </h1>
              <form class="space-y-4 md:space-y-6" action="" method="POST" enctype="multipart/form-data">
                  <!-- Full Name -->
                  <div>
                      <label for="full-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Full Name</label>
                      <input type="text" name="full-name" id="full-name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="YourName FatherName Surname" required>
                  </div>
                  <!-- Contact No -->
                  <div>
                      <label for="contact" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact No</label>
                      <input type="tel" name="contact" id="contact" pattern="\d{10}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Do not add +91" required>
                      <small class="text-xs text-gray-500 dark:text-gray-400">Enter a 10-digit number</small>
                  </div>
                  <!-- Parents Contact No -->
                  <div>
                      <label for="parents-contact" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Parents Contact No</label>
                      <input type="tel" name="parents-contact" id="parents-contact" pattern="\d{10}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                      <small class="text-xs text-gray-500 dark:text-gray-400">Enter a 10-digit number</small>
                  </div>
                  <!-- Email Id -->
                  <div>
                      <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email Id</label>
                      <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" onBlur="checkAvailability()" required>
                      <span id="user-availability-status" class="text-xs text-gray-500 dark:text-gray-400"></span>
                  </div>
                  <!-- Password -->
                  <div>
                      <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                      <input type="password" name="password" id="password" minlength="8" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                      <small class="text-xs text-gray-500 dark:text-gray-400">Minimum 8 characters</small>
                  </div>
                  <!-- Confirm Password -->
                  <div>
                      <label for="confirm-password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm Password</label>
                      <input type="password" name="confirm-password" id="confirm-password" minlength="8" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                      <small class="text-xs text-gray-500 dark:text-gray-400">Minimum 8 characters</small>
                  </div>
                  <!-- Birthday Date -->
                  <div>
                      <label for="bday" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Birthday Date</label>
                      <input type="date" name="bday" id="bday" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                  </div>
                  <!-- Hobbies -->
                  <div>
                      <label for="hobbies" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Hobbies</label>
                      <textarea name="hobbies" id="hobbies" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Chess, Swimming" required></textarea>
                  </div>
                  <!-- Extra Skills -->
                  <div>
                      <label for="skills" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Extra skill other than school/college learning</label>
                      <textarea name="skills" id="skills" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Musical instrument, Cooking, Software, GK, English, Acting, experience of professional work for any type of work" rows="5" required></textarea>
                  </div>
                  <!-- English Proficiency -->
                  <div>
                      <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your English Proficiency</label>
                      <div class="flex items-center">
                          <input type="radio" name="english" id="english1" value="Beginner" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600" required>
                          <label for="english1" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Beginner</label>
                      </div>
                      <div class="flex items-center mt-2">
                          <input type="radio" name="english" id="english2" value="Intermediate" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600" required>
                          <label for="english2" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Intermediate</label>
                      </div>
                      <div class="flex items-center mt-2">
                          <input type="radio" name="english" id="english3" value="Advance" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600" required>
                          <label for="english3" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Advance</label>
                      </div>
                  </div>
                  <!-- Blood Group -->
                  <div>
                      <label for="blood-group" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Blood Group</label>
                      <select name="blood-group" id="blood-group" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                          <option value="">Select Blood Group</option>
                          <option value="A+">A+</option>
                          <option value="A-">A-</option>
                          <option value="B+">B+</option>
                          <option value="B-">B-</option>
                          <option value="AB+">AB+</option>
                          <option value="AB-">AB-</option>
                          <option value="O+">O+</option>
                          <option value="O-">O-</option>
                      </select>
                  </div>
                  <!-- Mediclaim -->
                  <div>
                      <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Have you any Mediclaim from your home?</label>
                      <div class="flex items-center">
                          <input type="radio" name="mediclaim" id="mediclaim1" value="Yes" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600" required>
                          <label for="mediclaim1" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Yes</label>
                      </div>
                      <div class="flex items-center mt-2">
                          <input type="radio" name="mediclaim" id="mediclaim2" value="No" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600" required>
                          <label for="mediclaim2" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">No</label>
                      </div>
                      <div class="flex items-center mt-2">
                          <input type="radio" name="mediclaim" id="mediclaim3" value="Not Sure" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600" required>
                          <label for="mediclaim3" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Not Sure</label>
                      </div>
                  </div>
                  <!-- College Name -->
                  <div>
                      <label for="college-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">College Name</label>
                      <input type="text" name="college-name" id="college-name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                  </div>
                  <!-- Study With Year -->
                  <div>
                      <label for="study" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Study With Year</label>
                      <input type="text" name="study" id="study" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="3rd Year, Computer Engineering" required>
                  </div>
                  <!-- 10th Percentage -->
                  <div>
                      <label for="10th-percentage" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">10th Percentage</label>
                      <input type="text" name="10th-percentage" id="10th-percentage" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="85%" required>
                  </div>
                  <!-- 12th Percentage -->
                  <div>
                      <label for="12th-percentage" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">12th Percentage</label>
                      <input type="text" name="12th-percentage" id="12th-percentage" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="90%" required>
                  </div>
                  <!-- Diploma Semesters -->
                  <div>
                      <label for="Dsem" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">How Many semesters of Diploma do you complete?</label>
                      <input type="number" name="Dsem" id="Dsem" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="leave blank if Not applicable" oninput="generateSpiFields('diploma', this.value)" >
                      <div id="diploma-container"></div>
                  </div>
                  <!-- Bachelor Semesters -->
                  <div>
                      <label for="Bsem" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">How Many semesters of Bachelor's do you complete?</label>
                      <input type="number" name="Bsem" id="Bsem" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="leave blank if Not applicable" oninput="generateSpiFields('bachelor', this.value)" >
                      <div id="bachelor-container"></div>
                  </div>
                  <!-- Master Semesters -->
                  <div>
                      <label for="Msem" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">How Many semesters of Master's do you complete?</label>
                      <input type="number" name="Msem" id="Msem" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="leave blank if Not applicable" oninput="generateSpiFields('master', this.value)" >
                      <div id="master-container"></div>
                  </div>
                  <!-- Photo -->
                  <div>
                      <label for="photo" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Passport Size Photo</label>
                      <input type="file" name="photo" id="photo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                      <p>( <b><u>Warning</u></b> : Make it clear)</p>
                      <p><b>Maximum size: 1 mb</b></p>
                      <p><b>No PDF, ONLY PNG/JPEG/JPG</b></p>
                  </div>
                  <!-- Submit -->
                  <!-- Submit Button -->
<button 
  type="submit" name="submit"
  style="display: block; width: 100%; background-color: #2563EB; color: #FFFFFF; border: none; padding: 12px 0; font-size: 16px; font-weight: 600; text-align: center; border-radius: 8px; cursor: pointer;">
  Create an account
</button>

                  <p class="text-sm font-light text-gray-500 dark:text-gray-400">
                      Already have an account? <a href="index.php" class="font-medium text-primary-600 hover:underline dark:text-primary-500">Login here</a>
                  </p>
              </form>
          </div>
      </div>
  </div>
</section>
<script>
  function generateSpiFields(type, count) {
      let containerId;
      if (type === 'diploma') {
          containerId = 'diploma-container';
      } else if (type === 'bachelor') {
          containerId = 'bachelor-container';
      } else if (type === 'master') {
          containerId = 'master-container';
      }

      const container = document.getElementById(containerId);
      container.innerHTML = ''; // Clear existing fields

      for (let i = 1; i <= count; i++) {
          const input = document.createElement('input');
          input.type = 'text';
          input.name = `${type}-spi-sem${i}`;
          input.placeholder = `SPI for Semester ${i}`;
          input.className = 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500';
          container.appendChild(input);
          container.appendChild(document.createElement('br'));
      }
  }
</script>
</body>
</html>
