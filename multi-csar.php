#!/usr/bin/env php
<?php
$path = "/home/holystu/multi-csar/aux_bin/";
if(!file_exists($path."blossom5")) pErr('Please setup Multi-CSAR first!');
$opt = getopt("t:r:o:wh", array('nuc', 'pro', 'time', 'CSAR'));
$mesg = 'Usage: multi-csar.php [option] -t <target_contigs_file> -r <references_directory> --nuc/--pro';

if(isset($opt['h']) || empty($opt)){
print <<<END
$mesg
Option:
	-t <string>   Input file that contains the target draft genome in the multi-FASTA format

	-r <string>   Input directory that contains only reference genomes in the multi-FASTA format

	--nuc         Use NUCmer to identify markers between the target genome and each reference genome

	--pro         Use PROmer to identify markers between the target genome and each reference genome

	-w            Use the sequence identity-based weighting scheme

	-o <string>   Output directory that contains all the output files (the default is ./multi-csar_out)

	--CSAR        Keep the files generated by CSAR

	-h            Show help message

END;
exit(1);
}

$CWD = getcwd();
$target = isset($opt['t']) ? realpath($opt['t']) : '';
$refs = isset($opt['r']) ? realpath($opt['r']) : '';
$out_path = isset($opt['o']) ? $opt['o'] : $CWD.'/multi-csar_out';
$getWeight = isset($opt['w']) ? 1 : 0;
$getTime = isset($opt['time']) ? 1 : 0;
$CSAR = isset($opt['CSAR']) ? 1 : 0;

if(isset($opt['nuc']) && isset($opt['pro'])){
	pErr('Please specify either nuc or pro.');
}

$mum = isset($opt['nuc']) ? 'nuc' : '';
if($mum == '')
	$mum = isset($opt['pro']) ? 'pro' : '';
unset($opt);

if($target == ''){
	pErr('No such target genome');
}
elseif($refs == ''){
	pErr('No such references directory');
}
elseif($mum == ''){
	pErr('Please specify either NUCmer or PROmer to identify genetic markers');
}

if(!file_exists($out_path) && !mkdir($out_path, 0777, true))
	pErr('Invalid output directory');

$out_path = realpath($out_path);

$csar_para = $getWeight == 1 ? '--getWeight --noFna --time' : '--noFna --time';
$csar_allResults = '';
$weights = '';
$mumTime = 0;
$csarAgTime = 0;
$genFastaTime_each = 0;
$getWeightTime = 0;
$totalTime = getMicrotime();

if($ref_dir = opendir($refs)){
	$n = 0;
	while(($file = readdir($ref_dir)) !== false){
	    if($file[0] != '.'){
			p("Using reference: $file");
			chdir($out_path);
			@mkdir('ref'.++$n.'_out', 0777, true);
			@unlink('ref'.$n.'_out/ref.fna');
			symlink("$refs/$file", 'ref'.$n.'_out/ref.fna');
			@unlink('ref'.$n.'_out/target.fna');
			symlink($target, 'ref'.$n.'_out/target.fna');
			
			chdir('ref'.$n.'_out');
			system($path.'csar_v1.1.php -r ref.fna -t target.fna'." --$mum $csar_para", $retval);
			// need to check return value of csar, i.e., error or not
			if($retval != 0) pErr('Error occurs when running CSAR'); 

			$csar_allResults .= 'ref'.$n.'_out/csar_out/scaffolds.'.$mum.'.csar ';
			if($getTime == 1){
				$timeInfo = file('csar_out/csar_'.$mum.'.runTime');
				$mumTime += floatval(substr($timeInfo[1], 8, strrpos($timeInfo[1], ' ') - 8));
				$csarAgTime += floatval(substr($timeInfo[2], 16, strrpos($timeInfo[2], ' ') - 16));
			//	$genFastaTime_each += floatval(substr($timeInfo[3], 25, strrpos($timeInfo[3], ' ') - 25));
				$getWeightTime += floatval(substr($timeInfo[4], 12, strrpos($timeInfo[4], ' ') - 12));
			}
			if($getWeight == 1){
				$weights .= trim(file_get_contents('csar_out/'.$mum.'.coords.weight')).' ';
			}
		}
	}
	
	if($n == 0)
		pErr('Folder contains no reference file');
}
else{
	pErr('Invalid reference directory');
}
closedir($ref_dir);

if($getWeight == 1){
	$suf = "$mum.ws";
	$CSARtoBlossom_para = "-w $weights";
}
else{
	$suf = "$mum";
	$CSARtoBlossom_para = '';
}

$out = 'multi-csar.'.$suf.'.out';

chdir($out_path);

$genGraphTime = getMicrotime();
system($path."CSARtoBlossom_4 $csar_allResults -g outGraph.$suf -m outGCmap.$suf $CSARtoBlossom_para");
$genGraphTime = round(getMicrotime() - $genGraphTime, 3);

$blossomTime = getMicrotime();
system($path."blossom5 -e outGraph.$suf -w perfectM.$suf");
$blossomTime = round(getMicrotime() - $blossomTime, 3);

$recoverTime = getMicrotime();
system($path."BlossomRecover outGraph.$suf perfectM.$suf outGCmap.$suf $out");
$recoverTime = round(getMicrotime() - $recoverTime, 3);

if(1){
	unlink("outGraph.$suf");
	unlink("perfectM.$suf");
	unlink("outGCmap.$suf");
}

if($CSAR == 0){
	for($i = 1; $i <= $n; $i++){
		chdir($out_path.'/ref'.$i.'_out');
		array_map('unlink', glob("csar_out/*"));
		array_map('unlink', glob('*.fna'));
		rmdir('csar_out');
		chdir($out_path);
		rmdir('ref'.$i.'_out');
	}
}



$contigSeq = array();
$plus_o = '0';

$genFastaTime = getMicrotime();
parse_contig($out, $target);
genFnaFile($out);
$genFastaTime = round(getMicrotime() - $genFastaTime, 3);

$totalTime = round(getMicrotime() - $totalTime, 3);

p('Multi-CSAR is DONE!');
if($getTime == 1){
	p('*Running Time Info*');
	p('MUMmer: '.$mumTime);
	p('CSAR algorithm: '. $csarAgTime);
	p('Get weight: '. $getWeightTime);
	p('Generate graph: '. $genGraphTime);
	p('Generate fasta file: '. $genFastaTime);
	p('BlossomV: '.$blossomTime);
	p('Recover scaffolds: '.$recoverTime);
	p('Total Time: '.$totalTime);
}

/*** Functions ***/

function p($str){
	echo "$str\n";
}

function pErr($str){
	p("ERROR: $str");
	exit(1);
}

function parse_contig($contig_file, $multiFasta){
	global $contigSeq;
	$lines = array();
	$handle = fopen($multiFasta, 'r');
	while(($line = fgets($handle)) !== false){
		array_push($lines, rtrim($line));
	}
	fclose($handle);

	$forward = '';
	$reverse = '';
	$forward_arr = array();
	$reverse_arr = array();
	$pass = 0;

	foreach($lines as $k => $line){
		if($line == '') continue;
		
		if(substr($line, 0, 1) != '>'){
			array_push($forward_arr, $line."\n");
			array_push($reverse_arr, strrev($line)."\n");
		}
		
		if(substr($line, 0, 1) == '>' || !isset($lines[$k+1])){
			if($pass == 1){
				foreach($forward_arr as $each){
					$forward .= $each;
				}
				$n = count($reverse_arr);
				for($i = $n-1; $i>=0; $i--){
					$reverse .= $reverse_arr[$i];
				}

				$contigSeq[$fileName] = $forward;		
				$new_reverse = $reverse;
				$seqLen = strlen($reverse);
				for($i = 0; $i < $seqLen; $i++){
					if($reverse[$i] == 'A'){
						$new_reverse[$i] = 'T';
					}
					else if($reverse[$i] == 'T'){
						$new_reverse[$i] = 'A';
					}
					else if($reverse[$i] == 'C'){
						$new_reverse[$i] = 'G';
					}
					else if($reverse[$i] == 'G'){
						$new_reverse[$i] = 'C';
					}
					else if($reverse[$i] == 'a'){
						$new_reverse[$i] = 't';
					}
					else if($reverse[$i] == 't'){
						$new_reverse[$i] = 'a';
					}
					else if($reverse[$i] == 'c'){
						$new_reverse[$i] = 'g';
					}
					else if($reverse[$i] == 'g'){
						$new_reverse[$i] = 'c';
					}
				}
				$contigSeq["$fileName.reverse"] = $new_reverse;		
				$forward_arr = array();
				$reverse_arr = array();
				$forward = '';
				$reverse = '';
			}
			
			if(strstr($line, ' ')){
				$fileName = substr($line, 1, strpos($line, ' ')-1);
			}
			else{
				$fileName = substr($line, 1);
			}
		//	print "fileName = $fileName\n";
			$pass = 1;
		}
	}
}

function genFnaFile($outputFile_draft){
	global $contigSeq;
	global $plus_o;
	
	$list = file($outputFile_draft);
	$append = '';
	$edge = 0;
	$outSuffix = '.fna';
	file_put_contents($outputFile_draft.$outSuffix, '');
	foreach($list as $k => $each){
		if($each == "\n" || strstr($each, '>')){
			file_put_contents($outputFile_draft.$outSuffix, $each, FILE_APPEND);
			$edge = 1;
			continue;
		}

		if($edge == 0){
			$append = str_repeat('N', 100)."\n";
			file_put_contents($outputFile_draft.$outSuffix, $append, FILE_APPEND);
		}

		$partEach = trim(substr($each, 0, strpos($each,' ')+1));
		if(trim(substr($each, -2)) == $plus_o){
			file_put_contents($outputFile_draft.$outSuffix, $contigSeq[$partEach], FILE_APPEND);
		}
		else{
			file_put_contents($outputFile_draft.$outSuffix, $contigSeq["$partEach.reverse"], FILE_APPEND);
		}
		
		$edge = 0;
	}
}

function getMicrotime(){
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}

?>
