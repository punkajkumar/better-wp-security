<?php
if (!class_exists('BWPS_away')) {
	class BWPS_away {
	
		function isOn() {
			global $opts;
			return $opts['away_enable'];
		}
			
		function check() {
			global $opts;
			
			if ($opts['away_enable'] == 1) {
			
				$lTime = strtotime(get_date_from_gmt(date('Y-m-d H:i:s',time())));
			
				if ($opts['away_mode'] == 1) {
					if (date('a',$lTime) == "pm" && date('g',$lTime) != "12") {
						$linc = 12;
					}elseif (date('a',$lTime) == "am" && date('g',$lTime) == "12") {
						$linc = -12;
					}
				
					$local = ((date('g',$lTime) + $linc) * 60) + date('i',$lTime);
			
					if (date('a',$opts['away_start']) == "pm" && date('g',$opts['away_start']) != "12") {
						$sinc = 12;
					}elseif (date('a',$opts['away_start']) == "am" && date('g',$opts['away_start']) == "12") {
						$sinc = -12;
					}
				
					$start = ((date('g',$opts['away_start']) + $sinc) * 60) + date('i',$opts['away_start']);
				
					if (date('a',$opts['away_end']) == "pm" && date('g',$opts['away_end']) != "12") {
						$einc = 12;
					} elseif (date('a',$opts['away_end']) == "am" && date('g',$opts['away_end']) == "12") {
						$einc = -12;
					}
				
					$end = ((date('g',$opts['away_end']) + $einc) * 60) + date('i',$opts['away_end']);
				
					if ($start >= $end) {
						if ($local >= $start || $local < $end) {
							return true;
						}
					} else {
						if ($local >= $start && $local < $end) {
							return true;
						}
					}
				} else {	
					if ($lTime >= $opts['away_start'] && $lTime <= $opts['away_end']) {
						return true;
					}
				}
			}
			return false;
		}
	}
}