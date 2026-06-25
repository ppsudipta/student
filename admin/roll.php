<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roll Number Status</title>
    <style>
        body {font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; padding:20px; background-color:#f0f8ff; color:#333;}
        a{text-decoration:none;}
        .container {max-width:1200px; margin:0 auto; background:white; padding:25px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08);}
        h1 {text-align:center; color:#2c3e50; margin-bottom:30px; padding-bottom:15px; border-bottom:2px solid #ecf0f1;}
        .letter-section {margin-bottom:35px; page-break-inside:avoid;}
        .letter-header {background:linear-gradient(to right,#3498db,#2c3e50); color:white; padding:12px 15px; border-radius:8px; margin-bottom:15px; font-size:22px; font-weight:bold; box-shadow:0 3px 6px rgba(0,0,0,0.1);}
        .registration-grid {display:grid; grid-template-columns:repeat(auto-fill,minmax(85px,1fr)); gap:10px;}
        .reg-number {padding:10px 5px; text-align:center; border-radius:6px; font-size:14px; font-weight:500; transition:all 0.2s ease; box-shadow:0 2px 4px rgba(0,0,0,0.1); position:relative; cursor:pointer;}
        .occupied {background:linear-gradient(to bottom,#ff6b6b,#e74c3c); border:1px solid #c0392b; color:white; text-shadow:1px 1px 2px rgba(0,0,0,0.2);}
        .vacant {background:linear-gradient(to bottom,#7befb2,#2ecc71); border:1px solid #27ae60; color:white; text-shadow:1px 1px 2px rgba(0,0,0,0.2);}
        .reg-number:hover {transform:translateY(-3px); box-shadow:0 5px 10px rgba(0,0,0,0.15);}
        .filters {margin-bottom:25px; display:flex; gap:20px; flex-wrap:wrap; align-items:center; background:#f8f9fa; padding:15px; border-radius:8px;}
        .filters input,.filters select {padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:15px; outline:none;}
        .filters input:focus,.filters select:focus {border-color:#3498db; box-shadow:0 0 0 2px rgba(52,152,219,0.2);}
        .legend {display:flex; gap:20px; margin-bottom:25px; flex-wrap:wrap;}
        .legend-item {display:flex; align-items:center; gap:8px; padding:8px 15px; border-radius:6px; background:#f8f9fa;}
        .legend-color {width:22px; height:22px; border-radius:4px;}
        .print-btn {background:linear-gradient(to right,#3498db,#2980b9); color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-weight:600; transition:all 0.3s; box-shadow:0 3px 6px rgba(0,0,0,0.1);}
        .print-btn:hover {background:linear-gradient(to right,#2980b9,#3498db); transform:translateY(-2px); box-shadow:0 5px 10px rgba(0,0,0,0.15);}
        .student-info {display:none; position:absolute; background:white; border:1px solid #ddd; padding:12px; border-radius:6px; box-shadow:0 5px 15px rgba(0,0,0,0.1); z-index:100; width:220px; font-size:13px; text-align:left; bottom:100%; left:50%; transform:translateX(-50%); margin-bottom:10px;}
        .reg-number:hover .student-info {display:block;}
        .student-info h4 {margin:0 0 8px 0; color:#2c3e50; border-bottom:1px solid #eee; padding-bottom:5px;}
        .student-info p {margin:5px 0; color:#555;}
        .status-badge {position:absolute; top:3px; right:3px; font-size:10px; padding:3px 6px; border-radius:10px; font-weight:bold;}
        .status-ongoing {background:#2ecc71;color:white;}
        .status-suspended {background:#e74c3c;color:white;}
        .status-promoted {background:#3498db;color:white;}
        .status-completed {background:#9b59b6;color:white;}
        @media print {.filters,.print-btn {display:none;} .student-info{display:none!important;} .reg-number{box-shadow:none; border:1px solid #ddd!important; color:black!important; background:white!important;}}
        .stats {display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap;}
        .stat-box {flex:1; min-width:180px; background:white; padding:15px; border-radius:8px; box-shadow:0 3px 6px rgba(0,0,0,0.1); text-align:center;}
        .stat-number {font-size:28px; font-weight:bold; margin:10px 0;}
        .stat-occupied {color:#e74c3c;}
        .stat-vacant {color:#2ecc71;}
    </style>
</head>
<body>
    <div class="container">
        <h1>Roll Number Status</h1>
        
        <div class="stats">
            <div class="stat-box"><h3>Total Codes</h3><div class="stat-number" id="total-codes">0</div><p>All Roll Numbers</p></div>
            <div class="stat-box"><h3>Occupied</h3><div class="stat-number stat-occupied" id="occupied-codes">0</div><p>Currently in use</p></div>
            <div class="stat-box"><h3>Vacant</h3><div class="stat-number stat-vacant" id="vacant-codes">0</div><p>Available for use</p></div>
        </div>
        
        <div class="filters">
            <a class="print-btn" href="allregister.php">Back To All Registration</a>
            <div>
                <label for="letterFilter">Filter by Letter:</label>
                <select id="letterFilter" onchange="filterLetters()"><option value="all">All Letters</option></select>
            </div>
            <div>
                <label for="statusFilter">Filter by Status:</label>
                <select id="statusFilter" onchange="filterStatus()">
                    <option value="all">All Status</option>
                    <option value="occupied">Occupied Only</option>
                    <option value="vacant">Vacant Only</option>
                </select>
            </div>
            <div>
                <label for="search">Search Code:</label>
                <input type="text" id="search" placeholder="Enter code..." oninput="searchCode()">
            </div>
        </div>
        
        <div class="legend">
            <div class="legend-item"><div class="legend-color" style="background:linear-gradient(to bottom,#ff6b6b,#e74c3c);"></div><span>Occupied</span></div>
            <div class="legend-item"><div class="legend-color" style="background:linear-gradient(to bottom,#7befb2,#2ecc71);"></div><span>Vacant</span></div>
        </div>
        
        <div id="registrationGrid"></div>
    </div>

    <script>
        let studentData = [];
        let registrationData = {};

        // Fetch students dynamically
        document.addEventListener('DOMContentLoaded', () => {
            fetch("fetch_students.php")
                .then(res => res.json())
                .then(data => {
                    studentData = data;
                    const occupiedCodes = studentData.map(s => s.registration_code.toUpperCase());

                    // Build registrationData A-Z + REG
                    const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split('');
                    registrationData = {};
                    letters.forEach(letter => {
                        registrationData[letter] = {};
                        for (let i = 1; i <= 100; i++) {
                            const code = letter + i;
                            registrationData[letter][code] = occupiedCodes.includes(code);
                        }
                    });

                    registrationData["REG"] = {};
                    occupiedCodes.filter(c => c.startsWith("REG")).forEach(code => {
                        registrationData["REG"][code] = true;
                    });

                    populateRegistrationData();
                })
                .catch(err => console.error("Error fetching students:", err));
        });

        // Build UI
        function populateRegistrationData() {
            const container = document.getElementById('registrationGrid');
            container.innerHTML = '';
            const letterFilter = document.getElementById('letterFilter');
            letterFilter.innerHTML = '<option value="all">All Letters</option>';

            let totalCodes = 0, occupiedCount = 0, vacantCount = 0;

            Object.keys(registrationData).forEach(letter => {
                // Add filter option
                const option = document.createElement('option');
                option.value = letter; option.textContent = letter;
                letterFilter.appendChild(option);

                const section = document.createElement('div');
                section.className = 'letter-section';
                section.id = `letter-${letter}`;

                const header = document.createElement('div');
                header.className = 'letter-header';
                header.textContent = `Letter ${letter}`;
                section.appendChild(header);

                const grid = document.createElement('div');
                grid.className = 'registration-grid';

                Object.keys(registrationData[letter]).forEach(code => {
                    totalCodes++;
                    const isOccupied = registrationData[letter][code];
                    if (isOccupied) occupiedCount++; else vacantCount++;

                    const student = isOccupied ? studentData.find(s => s.registration_code.toUpperCase() === code) : null;
                    const regNumber = document.createElement('div');
                    regNumber.className = `reg-number ${isOccupied ? 'occupied':'vacant'}`;
                    regNumber.textContent = code;
                    regNumber.setAttribute('data-status', isOccupied ? 'occupied':'vacant');

                    if (isOccupied && student) {
                        const badge = document.createElement('span');
                        badge.className = `status-badge status-${student.status}`;
                        badge.textContent = student.status.charAt(0).toUpperCase();
                        regNumber.appendChild(badge);

                        const info = document.createElement('div');
                        info.className = 'student-info';
                        info.innerHTML = `
                            <h4>${student.name}</h4>
                            <p><strong>Status:</strong> ${student.status}</p>
                            <p><strong>Course:</strong> ${student.course}</p>
                            <p><strong>ID:</strong> ${student.id}</p>`;
                        regNumber.appendChild(info);
                    }
                    grid.appendChild(regNumber);
                });

                section.appendChild(grid);
                container.appendChild(section);
            });

            // Stats
            document.getElementById('total-codes').textContent = totalCodes;
            document.getElementById('occupied-codes').textContent = occupiedCount;
            document.getElementById('vacant-codes').textContent = vacantCount;
        }

        // Filters
        function filterLetters() {
            const selected = document.getElementById('letterFilter').value;
            document.querySelectorAll('.letter-section').forEach(sec => {
                sec.style.display = (selected==='all'||sec.id===`letter-${selected}`)?'block':'none';
            });
            filterStatus();
        }
        function filterStatus() {
            const status = document.getElementById('statusFilter').value;
            document.querySelectorAll('.reg-number').forEach(reg => {
                if (status==='all') reg.style.display='block';
                else reg.style.display = reg.getAttribute('data-status')===status?'block':'none';
            });
        }
        function searchCode() {
            const search = document.getElementById('search').value.toUpperCase();
            if (!search) {
                document.querySelectorAll('.letter-section').forEach(sec => sec.style.display='block');
                document.querySelectorAll('.reg-number').forEach(reg => reg.style.display='block');
                return;
            }
            document.querySelectorAll('.letter-section').forEach(sec => sec.style.display='none');
            document.querySelectorAll('.reg-number').forEach(reg => {
                if (reg.textContent.includes(search)) {
                    reg.style.display='block';
                    reg.closest('.letter-section').style.display='block';
                } else reg.style.display='none';
            });
        }
    </script>
</body>
</html>
