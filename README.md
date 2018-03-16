Multi-CSAR
==========
A multiple reference-based contig scaffolder using algebraic rearrangements

Description
-----------
Multi-CSAR is a scaffolding tool that can order and orient the contigs of a target draft genome based on multiple complete and/or incomplete reference genomes of related organisms.

System requirements
-------------------
To run Multi-CSAR, the following packages are required to be installed on your system and also available in your $PATH.

1. PHP (from 5): http://php.net/downloads.php
2. MUMmer: http://mummer.sourceforge.net
	
Installation
------------
The installation steps are as follows:

1. Download the zip file Multi-CSAR-master.zip and upzip it on your system.
2. Run the following command to install Multi-CSAR.

	php setup.php

Usage
-----
Options of running Multi-CSAR:

```
-t <string>   Input file that contains the target draft genome in the multi-FASTA format

-r <string>   Input directory that contains only reference genomes in the multi-FASTA format

--nuc         Use NUCmer to identify markers between the target genome and each reference genome

--pro         Use PROmer to identify markers between the target genome and each reference genome

-w            Use the sequence identity-based weighting scheme

-o <string>   Output directory that contains all the output files (the default is ./multi-csar_out)

--CSAR        Keep the files generated by CSAR

-h            Show help message

```
Suppose that multi-csar.php is already in your $PATH. Then the following is an example to run Multi-CSAR:

	multi-csar.php -t example/Burkholderia_target.fna -r example/reference_genomes/ --nuc -o example_out

Note that either --nuc or --pro (but not both) should be used when running Multi-CSAR.

Output
------
Multi-CSAR generates the following files in the output directory:

1.	`multi-csar.nuc.out` (if NUCmer is used) or `multi-csar.pro.out` (if PROmer is used)

The above file lists all the scaffolds returned by Multi-CSAR.
These scaffolds are numbered arbitrarily and each of them contains ordered and oriented contigs, as exemplified as follows.
		
```
>Scaffold_1
<contig_name_a> <orientation_a>
<contig_name_b> <orientation_b>
<contig_name_c> <orientation_c>
...

>Scaffold_2
<contig_name_d> <orientation_d>
<contig_name_e> <orientation_e>
<contig_name_f> <orientation_f>
...	
```

Note that the orientation of a contig is represented by an integer number 0 or 1, where 0 indicates the forward (i.e., original) orientation of the contig, while 1 indicates the reverse orientation of the contig.

2.	`multi-csar.nuc.out.fna` (if NUCmer is used) or `multi-csar.pro.out.fna` (if PROmer is used)

The above file contains the sequences of scaffolds in multi-FASTA format. Note that for a scaffold with multiple contigs, a stretch of 100 undetermined bases (N), serving as a spacer, is inserted between any two consecutive contigs.

Contact Information
----
If you encounter fatal errors when running Multi-CSAR, please contact Mr. Kun-Tze Chen (email: holystu@gmail.com)