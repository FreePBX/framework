<?php
/*=======================================================================
// File:	JPGRAPH_GRADIENT.PHP
// Description:	Create a color gradient
// Created: 	2003-02-01
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id$
//
// License:	This code is released under QPL
// Copyright (C) 2003 Johan Persson
//========================================================================
*/

  
//===================================================
// CLASS Gradient
// Description: Handles gradient fills. This is to be
// considered a "friend" class of Class Image.
//===================================================
class Gradient {
    var $img=null;
    var $numcolors=100;
//---------------
// CONSTRUCTOR
    function Gradient(&$img) {
	$this->img = $img;
    }


    function SetNumColors($aNum) {
	$this->numcolors=$aNum;
    }
//---------------
// PUBLIC METHODS	
    // Produce a gradient filled rectangle with a smooth transition between
    // two colors.
    // ($xl,$yt) 	Top left corner
    // ($xr,$yb)	Bottom right
    // $from_color	Starting color in gradient
    // $to_color	End color in the gradient
    // $style		Which way is the gradient oriented?
    function FilledRectangle($xl,$yt,$xr,$yb,$from_color,$to_color,$style=1) {
	switch( $style ) {	
	    case GRAD_VER:  
		$steps = abs($xr-$xl);
		$delta = $xr>=$xl ? 1 : -1;
		$this->GetColArray($from_color,$to_color,$steps,$colors,$this->numcolors);
		for( $i=0, $x=$xl; $i < $steps; ++$i ) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yt,$x,$yb);
		    $x += $delta;
		}
		break;

	    case GRAD_HOR: 
		$steps = abs($yb-$yt);
		$delta = $yb>=$yt ? 1 : -1;
		$this->GetColArray($from_color,$to_color,$steps,$colors,$this->numcolors);
		for($i=0,$y=$yt; $i < $steps; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($xl,$y,$xr,$y);
		    $y += $delta;
		}
		break;

	    case GRAD_MIDHOR: 
		$steps = abs($yb-$yt)/2;
		$delta = $yb >= $yt ? 1 : -1;
		$this->GetColArray($from_color,$to_color,$steps,$colors,$this->numcolors);
		for($y=$yt, $i=0; $i < $steps;  ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($xl,$y,$xr,$y);
		    $y += $delta;
		}
		--$i;
		if( abs($yb-$yt) % 2 == 1 ) --$steps;
		for($j=0; $j < $steps; ++$j, --$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($xl,$y,$xr,$y);
		    $y += $delta;
		}
		$this->img->Line($xl,$y,$xr,$y);
		break;

	    case GRAD_MIDVER: 
		$steps = round(abs($xr-$xl)/2);
		$delta = $xr>=$xl ? 1 : -1;
		$this->GetColArray($from_color,$to_color,$steps,$colors,$this->numcolors);
		for($x=$xl, $i=0; $i < $steps; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		--$i;
		if( abs($xr-$xl) % 2 == 1 ) --$steps;
		for($j=0; $j < $steps; ++$j, --$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		$this->img->Line($x,$yb,$x,$yt);		
		break;

	    case GRAD_WIDE_MIDVER: 
		$diff = round(abs($xr-$xl));
		$steps = floor(abs($diff)/3);
		$firststep = $diff - 2*$steps ; 
		$delta = $xr >= $xl ? 1 : -1;
		$this->GetColArray($from_color,$to_color,$firststep,$colors,$this->numcolors);
		for($x=$xl, $i=0; $i < $firststep; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		--$i;
		$this->img->current_color = $colors[$i];
		for($j=0; $j< $steps; ++$j) {
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		
		for($j=0; $j < $steps; ++$j, --$i) {
		    $this->img->current_color = $colors[$i];				
		    $this->img->Line($x,$yb,$x,$yt);	
		    $x += $delta;
		}				
		break;

	    case GRAD_WIDE_MIDHOR:
		$diff = round(abs($yb-$yt));
		$steps = floor(abs($diff)/3);
		$firststep = $diff - 2*$steps ; 
		$delta = $yb >= $yt? 1 : -1;
		$this->GetColArray($from_color,$to_color,$firststep,$colors,$this->numcolors);
		for($y=$yt, $i=0; $i < $firststep;  ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($xl,$y,$xr,$y);
		    $y += $delta;
		}
		--$i;
		$this->img->current_color = $colors[$i];
		for($j=0; $j < $steps; ++$j) {
		    $this->img->Line($xl,$y,$xr,$y);
		    $y += $delta;
		}
		for($j=0; $j < $steps; ++$j, --$i) {
		    $this->img->current_color = $colors[$i];				
		    $this->img->Line($xl,$y,$xr,$y);
		    $y += $delta;
		}				
		break;	    

	    case GRAD_LEFT_REFLECTION: 
		$steps1 = round(0.3*abs($xr-$xl));
		$delta = $xr>=$xl ? 1 : -1;		

		$this->GetColArray($from_color.':1.3',$to_color,$steps1,$colors,$this->numcolors);
		for($x=$xl, $i=0; $i < $steps1; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		$steps2 = max(1,round(0.08*abs($xr-$xl)));
		$this->img->SetColor($to_color);
		for($j=0; $j< $steps2; ++$j) {
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		$steps = abs($xr-$xl)-$steps1-$steps2;
		$this->GetColArray($to_color,$from_color,$steps,$colors,$this->numcolors);   
		for($i=0; $i < $steps; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		break;

	    case GRAD_RIGHT_REFLECTION: 
		$steps1 = round(0.7*abs($xr-$xl));
		$delta = $xr>=$xl ? 1 : -1;		

		$this->GetColArray($from_color,$to_color,$steps1,$colors,$this->numcolors);
		for($x=$xl, $i=0; $i < $steps1; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		$steps2 = max(1,round(0.08*abs($xr-$xl)));
		$this->img->SetColor($to_color);
		for($j=0; $j< $steps2; ++$j) {
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		$steps = abs($xr-$xl)-$steps1-$steps2;
		$this->GetColArray($to_color,$from_color.':1.3',$steps,$colors,$this->numcolors);   
		for($i=0; $i < $steps; ++$i) {
		    $this->img->current_color = $colors[$i];
		    $this->img->Line($x,$yb,$x,$yt);
		    $x += $delta;
		}
		break;


	    case GRAD_CENTER: 
		$steps = floor(min(($yb-$yt)+1,($xr-$xl)+1)/2);	
		$this->GetColArray($from_color,$to_color,$steps,$colors,$this->numcolors);
		$dx = ($xr-$xl)/2;
		$dy = ($yb-$yt)/2;
		$x=$xl;$y=$yt;$x2=$xr;$y2=$yb;
		for($x=$xl, $i=0; $x < $xl+$dx && $y < $yt+$dy ; ++$x, ++$y, --$x2, --$y2, ++$i) {
		    assert( $i < count($colors));
		    $this->img->current_color = $colors[$i];			
		    $this->img->Rectangle($x,$y,$x2,$y2);
		}
		$this->img->Line($x,$y,$x2,$y2);
		break;

	    default:
		die("JpGraph Error: Unknown gradient style (=$style).");
		break;
	}
    }

    // Fill a special case of a polygon with a flat bottom
    // with a gradient. Can be used for filled line plots.
    // Please note that this is NOT a generic gradient polygon fill
    // routine. It assumes that the bottom is flat (like a drawing
    // of a mountain)
    function FilledFlatPolygon($pts,$from_color,$to_color) {
	if( count($pts) == 0 ) return;
	
	$maxy=$pts[1];
	$miny=$pts[1];		
	$n = count($pts) ;
	for( $i=0, $idx=0; $i < $n; $i += 2) {
	    $x = round($pts[$i]);
	    $y = round($pts[$i+1]);
	    $miny = min($miny,$y);
	    $maxy = max($maxy,$y);
	}
	    
	$colors = array();
	$this->GetColArray($from_color,$to_color,abs($maxy-$miny)+1,$colors,$this->numcolors);
	for($i=$miny, $idx=0; $i <= $maxy; ++$i ) {
	    $colmap[$i] = $colors[$idx++]; 
	}

	$n = count($pts)/2 ;
	$idx = 0 ;
	while( $idx < $n-1 ) {
	    $p1 = array(round($pts[$idx*2]),round($pts[$idx*2+1]));
	    $p2 = array(round($pts[++$idx*2]),round($pts[$idx*2+1]));
		
	    // Find the largest rectangle we can fill
	    $y = max($p1[1],$p2[1]) ;
	    for($yy=$maxy; $yy > $y; --$yy) {
		$this->img->current_color = $colmap[$yy];
		$this->img->Line($p1[0],$yy,$p2[0]-1,$yy);
	    }
	    
	    if( $p1[1] == $p2[1] ) continue; 

	    // Fill the rest using lines (slow...)
	    $slope = ($p2[0]-$p1[0])/($p1[1]-$p2[1]);
	    $x1 = $p1[0];
	    $x2 = $p2[0]-1;
	    $start = $y;
	    if( $p1[1] > $p2[1] ) {
		while( $y >= $p2[1] ) {
		    $x1=$slope*($start-$y)+$p1[0];
		    $this->img->current_color = $colmap[$y];
		    $this->img->Line($x1,$y,$x2,$y);
		    --$y;
		} 
	    }
	    else {
		while( $y >= $p1[1] ) {
		    $x2=$p2[0]+$slope*($start-$y);
		    $this->img->current_color = $colmap[$y];
		    $this->img->Line($x1,$y,$x2,$y);
		    --$y;
		} 
	    }
	}
    }

//---------------
// PRIVATE METHODS	
    // Add to the image color map the necessary colors to do the transition
    // between the two colors using $numcolors intermediate colors
    function GetColArray($from_color,$to_color,$arr_size,&$colors,$numcols=100) {
	if( $arr_size==0 ) return;
	// If color is given as text get it's corresponding r,g,b values
	$from_color = $this->img->rgb->Color($from_color);
	$to_color = $this->img->rgb->Color($to_color);
		
	$rdelta=($to_color[0]-$from_color[0])/$numcols;
	$gdelta=($to_color[1]-$from_color[1])/$numcols;
	$bdelta=($to_color[2]-$from_color[2])/$numcols;
	$colorsperstep	= $numcols/$arr_size;
	$prevcolnum	= -1;
	$from_alpha = $from_color[3];
	$to_alpha = $to_color[3];
	$adelta = ( $to_alpha - $from_alpha ) / $numcols ;
	for ($i=0; $i < $arr_size; ++$i) {
	    $colnum = floor($colorsperstep*$i);
	    if ( $colnum == $prevcolnum ) 
		$colors[$i]	= $colidx;
	    else {
		$r = floor($from_color[0] + $colnum*$rdelta);
		$g = floor($from_color[1] + $colnum*$gdelta);
		$b = floor($from_color[2] + $colnum*$bdelta);
		$alpha = $from_alpha + $colnum*$adelta;
		$colidx = $this->img->rgb->Allocate(sprintf("#%02x%02x%02x",$r,$g,$b),$alpha);
		$colors[$i] = $colidx;
	    }
	    $prevcolnum = $colnum;
	}
    }	
} // Class

?>
