<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Representative catalog of Philippine higher-education institutions.
 *
 * Hand-curated from publicly known HEIs across regions for local/offline use.
 * It is NOT an exhaustive CHED list; students whose school is missing can use
 * the manual entry fallback. Official institution codes are left null rather
 * than guessed.
 */
class SchoolSeeder extends Seeder
{
    private const NCR = 'National Capital Region (NCR)';

    private const CAR = 'Cordillera Administrative Region (CAR)';

    private const REGION_I = 'Region I (Ilocos Region)';

    private const REGION_II = 'Region II (Cagayan Valley)';

    private const REGION_III = 'Region III (Central Luzon)';

    private const REGION_IV_A = 'Region IV-A (CALABARZON)';

    private const MIMAROPA = 'MIMAROPA Region';

    private const REGION_V = 'Region V (Bicol Region)';

    private const REGION_VI = 'Region VI (Western Visayas)';

    private const REGION_VII = 'Region VII (Central Visayas)';

    private const NIR = 'Negros Island Region (NIR)';

    private const REGION_VIII = 'Region VIII (Eastern Visayas)';

    private const REGION_IX = 'Region IX (Zamboanga Peninsula)';

    private const REGION_X = 'Region X (Northern Mindanao)';

    private const REGION_XI = 'Region XI (Davao Region)';

    private const REGION_XII = 'Region XII (SOCCSKSARGEN)';

    private const CARAGA = 'Region XIII (Caraga)';

    private const BARMM = 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->schools() as [$name, $abbreviation, $city, $province, $region, $keywords]) {
            $attributes = [
                'abbreviation' => $abbreviation,
                'city' => $city,
                'province' => $province,
                'region' => $region,
                'search_keywords' => $keywords,
            ];

            // Case-insensitive match so "far eastern university" is reused, not duplicated.
            $existingSchool = School::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->first();

            if ($existingSchool) {
                // Name and active status are left as-is so admin edits survive re-seeding.
                $existingSchool->update($attributes);

                continue;
            }

            School::create([
                'name' => $name,
                ...$attributes,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array{string, ?string, string, string, string, ?string}>
     */
    private function schools(): array
    {
        return [
            // National Capital Region
            ['Far Eastern University', 'FEU', 'Manila', 'Metro Manila', self::NCR, 'Far Eastern FEU Manila'],
            ['FEU Institute of Technology', 'FEU Tech', 'Manila', 'Metro Manila', self::NCR, 'Far Eastern University Institute of Technology FIT'],
            ['FEU Diliman', null, 'Quezon City', 'Metro Manila', self::NCR, 'Far Eastern University Diliman'],
            ['FEU Alabang', null, 'Muntinlupa', 'Metro Manila', self::NCR, 'Far Eastern University Alabang'],
            ['University of the Philippines Diliman', 'UPD', 'Quezon City', 'Metro Manila', self::NCR, 'UP Diliman'],
            ['University of the Philippines Manila', 'UPM', 'Manila', 'Metro Manila', self::NCR, 'UP Manila'],
            ['University of Santo Tomas', 'UST', 'Manila', 'Metro Manila', self::NCR, null],
            ['De La Salle University', 'DLSU', 'Manila', 'Metro Manila', self::NCR, 'La Salle Taft'],
            ['De La Salle-College of Saint Benilde', 'DLS-CSB', 'Manila', 'Metro Manila', self::NCR, 'Benilde CSB La Salle'],
            ['Ateneo de Manila University', 'ADMU', 'Quezon City', 'Metro Manila', self::NCR, 'Ateneo'],
            ['Polytechnic University of the Philippines', 'PUP', 'Manila', 'Metro Manila', self::NCR, null],
            ['Technological University of the Philippines', 'TUP', 'Manila', 'Metro Manila', self::NCR, null],
            ['Mapúa University', null, 'Manila', 'Metro Manila', self::NCR, 'Mapua Institute of Technology MIT'],
            ['National University', 'NU', 'Manila', 'Metro Manila', self::NCR, 'NU Manila'],
            ['Adamson University', 'AdU', 'Manila', 'Metro Manila', self::NCR, null],
            ['University of the East', 'UE', 'Manila', 'Metro Manila', self::NCR, null],
            ['Pamantasan ng Lungsod ng Maynila', 'PLM', 'Manila', 'Metro Manila', self::NCR, 'University of the City of Manila'],
            ['Lyceum of the Philippines University', 'LPU', 'Manila', 'Metro Manila', self::NCR, 'Lyceum'],
            ['San Beda University', 'SBU', 'Manila', 'Metro Manila', self::NCR, 'San Beda'],
            ['Colegio de San Juan de Letran', 'Letran', 'Manila', 'Metro Manila', self::NCR, null],
            ['Philippine Normal University', 'PNU', 'Manila', 'Metro Manila', self::NCR, null],
            ['Centro Escolar University', 'CEU', 'Manila', 'Metro Manila', self::NCR, null],
            ['Arellano University', 'AU', 'Manila', 'Metro Manila', self::NCR, null],
            ['Emilio Aguinaldo College', 'EAC', 'Manila', 'Metro Manila', self::NCR, null],
            ['Our Lady of Fatima University', 'OLFU', 'Valenzuela', 'Metro Manila', self::NCR, 'Fatima'],
            ['University of Makati', 'UMak', 'Makati', 'Metro Manila', self::NCR, null],
            ['Asia Pacific College', 'APC', 'Makati', 'Metro Manila', self::NCR, null],
            ['Rizal Technological University', 'RTU', 'Mandaluyong', 'Metro Manila', self::NCR, null],
            ['Jose Rizal University', 'JRU', 'Mandaluyong', 'Metro Manila', self::NCR, null],
            ['Quezon City University', 'QCU', 'Quezon City', 'Metro Manila', self::NCR, null],
            ['Miriam College', 'MC', 'Quezon City', 'Metro Manila', self::NCR, null],
            ['Trinity University of Asia', 'TUA', 'Quezon City', 'Metro Manila', self::NCR, null],
            ['University of Caloocan City', 'UCC', 'Caloocan', 'Metro Manila', self::NCR, null],
            ['Marikina Polytechnic College', 'MPC', 'Marikina', 'Metro Manila', self::NCR, null],
            ['Pamantasan ng Lungsod ng Pasig', 'PLP', 'Pasig', 'Metro Manila', self::NCR, null],

            // Cordillera Administrative Region
            ['Saint Louis University', 'SLU', 'Baguio City', 'Benguet', self::CAR, null],
            ['University of the Philippines Baguio', 'UPB', 'Baguio City', 'Benguet', self::CAR, 'UP Baguio'],
            ['University of Baguio', 'UB', 'Baguio City', 'Benguet', self::CAR, null],
            ['Benguet State University', 'BSU', 'La Trinidad', 'Benguet', self::CAR, null],

            // Region I
            ['Mariano Marcos State University', 'MMSU', 'Batac City', 'Ilocos Norte', self::REGION_I, null],
            ['University of Northern Philippines', 'UNP', 'Vigan City', 'Ilocos Sur', self::REGION_I, null],
            ['Pangasinan State University', 'PSU', 'Lingayen', 'Pangasinan', self::REGION_I, null],

            // Region II
            ['Cagayan State University', 'CSU', 'Tuguegarao City', 'Cagayan', self::REGION_II, null],
            ['Isabela State University', 'ISU', 'Echague', 'Isabela', self::REGION_II, null],

            // Region III
            ['Bulacan State University', 'BulSU', 'Malolos', 'Bulacan', self::REGION_III, null],
            ['Holy Angel University', 'HAU', 'Angeles City', 'Pampanga', self::REGION_III, null],
            ['Angeles University Foundation', 'AUF', 'Angeles City', 'Pampanga', self::REGION_III, null],
            ['Don Honorio Ventura State University', 'DHVSU', 'Bacolor', 'Pampanga', self::REGION_III, null],
            ['Central Luzon State University', 'CLSU', 'Science City of Muñoz', 'Nueva Ecija', self::REGION_III, 'Munoz'],
            ['Tarlac State University', 'TSU', 'Tarlac City', 'Tarlac', self::REGION_III, null],

            // Region IV-A
            ['University of the Philippines Los Baños', 'UPLB', 'Los Baños', 'Laguna', self::REGION_IV_A, 'UP Los Banos'],
            ['Laguna State Polytechnic University', 'LSPU', 'Santa Cruz', 'Laguna', self::REGION_IV_A, null],
            ['Cavite State University', 'CvSU', 'Indang', 'Cavite', self::REGION_IV_A, null],
            ['De La Salle University-Dasmariñas', 'DLSU-D', 'Dasmariñas', 'Cavite', self::REGION_IV_A, 'La Salle Dasmarinas'],
            ['Adventist University of the Philippines', 'AUP', 'Silang', 'Cavite', self::REGION_IV_A, null],
            ['Batangas State University', 'BatStateU', 'Batangas City', 'Batangas', self::REGION_IV_A, null],
            ['Lyceum of the Philippines University-Batangas', 'LPU-B', 'Batangas City', 'Batangas', self::REGION_IV_A, 'Lyceum Batangas'],
            ['University of Rizal System', 'URS', 'Tanay', 'Rizal', self::REGION_IV_A, null],
            ['Southern Luzon State University', 'SLSU', 'Lucban', 'Quezon', self::REGION_IV_A, null],

            // MIMAROPA
            ['Palawan State University', 'PSU', 'Puerto Princesa City', 'Palawan', self::MIMAROPA, null],

            // Region V
            ['Bicol University', 'BU', 'Legazpi City', 'Albay', self::REGION_V, null],
            ['Ateneo de Naga University', 'AdNU', 'Naga City', 'Camarines Sur', self::REGION_V, 'Ateneo Naga'],

            // Region VI
            ['University of the Philippines Visayas', 'UPV', 'Miagao', 'Iloilo', self::REGION_VI, 'UP Visayas'],
            ['West Visayas State University', 'WVSU', 'Iloilo City', 'Iloilo', self::REGION_VI, null],
            ['Central Philippine University', 'CPU', 'Iloilo City', 'Iloilo', self::REGION_VI, null],
            ['University of San Agustin', 'USA', 'Iloilo City', 'Iloilo', self::REGION_VI, null],

            // Negros Island Region
            ['University of St. La Salle', 'USLS', 'Bacolod City', 'Negros Occidental', self::NIR, 'La Salle Bacolod'],
            ['Silliman University', 'SU', 'Dumaguete City', 'Negros Oriental', self::NIR, null],

            // Region VII
            ['University of San Carlos', 'USC', 'Cebu City', 'Cebu', self::REGION_VII, null],
            ['University of the Philippines Cebu', 'UP Cebu', 'Cebu City', 'Cebu', self::REGION_VII, null],
            ['Cebu Technological University', 'CTU', 'Cebu City', 'Cebu', self::REGION_VII, null],
            ['University of Cebu', 'UC', 'Cebu City', 'Cebu', self::REGION_VII, null],
            ['Cebu Institute of Technology-University', 'CIT-U', 'Cebu City', 'Cebu', self::REGION_VII, null],
            ['University of San Jose-Recoletos', 'USJ-R', 'Cebu City', 'Cebu', self::REGION_VII, null],

            // Region VIII
            ['Visayas State University', 'VSU', 'Baybay City', 'Leyte', self::REGION_VIII, null],
            ['Eastern Visayas State University', 'EVSU', 'Tacloban City', 'Leyte', self::REGION_VIII, null],

            // Region IX
            ['Western Mindanao State University', 'WMSU', 'Zamboanga City', 'Zamboanga del Sur', self::REGION_IX, null],
            ['Ateneo de Zamboanga University', 'AdZU', 'Zamboanga City', 'Zamboanga del Sur', self::REGION_IX, 'Ateneo Zamboanga'],

            // Region X
            ['Xavier University-Ateneo de Cagayan', 'XU', 'Cagayan de Oro City', 'Misamis Oriental', self::REGION_X, 'Ateneo de Cagayan'],
            ['University of Science and Technology of Southern Philippines', 'USTP', 'Cagayan de Oro City', 'Misamis Oriental', self::REGION_X, null],
            ['Mindanao State University-Iligan Institute of Technology', 'MSU-IIT', 'Iligan City', 'Lanao del Norte', self::REGION_X, null],
            ['Central Mindanao University', 'CMU', 'Maramag', 'Bukidnon', self::REGION_X, null],

            // Region XI
            ['University of the Philippines Mindanao', 'UP Mindanao', 'Davao City', 'Davao del Sur', self::REGION_XI, null],
            ['Ateneo de Davao University', 'AdDU', 'Davao City', 'Davao del Sur', self::REGION_XI, 'Ateneo Davao'],
            ['University of Mindanao', 'UM', 'Davao City', 'Davao del Sur', self::REGION_XI, null],
            ['University of Southeastern Philippines', 'USeP', 'Davao City', 'Davao del Sur', self::REGION_XI, null],

            // Region XII
            ['Notre Dame of Marbel University', 'NDMU', 'Koronadal City', 'South Cotabato', self::REGION_XII, null],
            ['University of Southern Mindanao', 'USM', 'Kabacan', 'Cotabato', self::REGION_XII, null],

            // Caraga
            ['Caraga State University', 'CSU', 'Butuan City', 'Agusan del Norte', self::CARAGA, null],
            ['Father Saturnino Urios University', 'FSUU', 'Butuan City', 'Agusan del Norte', self::CARAGA, 'Urios'],

            // BARMM
            ['Mindanao State University', 'MSU', 'Marawi City', 'Lanao del Sur', self::BARMM, 'MSU Marawi'],
            ['Cotabato State University', 'CotSU', 'Cotabato City', 'Maguindanao del Norte', self::BARMM, null],
        ];
    }
}
