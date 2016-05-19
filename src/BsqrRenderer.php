<?php

namespace com\peterbodnar\bsqrrenderer;

use com\peterbodnar\mx2svg\MxToSvg;
use com\peterbodnar\qrcoder\QrCoder;
use com\peterbodnar\svg\Svg;



/**
 * Bysquare image renderer
 */
class BsqrRenderer {


	const LOGO_NONE = "NONE";
	const LOGO_PAY = "PAY";
	const LOGO_INVOICE = "INVOICE";

	const LOGO_BOTTOM = "BOTTOM";
	const LOGO_RIGHT = "RIGHT";
	const LOGO_TOP = "TOP";
	const LOGO_LEFT = "LEFT";

	/** Level L, ~7% correction. */
	const EC_LEVEL_L = QrCoder::EC_LEVEL_L;
	/** Level M, ~15% correction. */
	const EC_LEVEL_M = QrCoder::EC_LEVEL_M;
	/** Level Q, ~25% correction. */
	const EC_LEVEL_Q = QrCoder::EC_LEVEL_Q;
	/** Level H, ~30% correction. */
	const EC_LEVEL_H = QrCoder::EC_LEVEL_H;


	/** @var int|float|null */
	protected $innerSize = NULL;
	/** @var int|float|null */
	protected $maxWidth = NULL;
	/** @var int|float|null */
	protected $maxHeight = NULL;
	/** @var string */
	protected $unit = "";
	/** @var bool */
	protected $showBorder = true;
	/** @var string */
	protected $logoPosition = self::LOGO_BOTTOM;
	/** @var string */
	protected $colorPrimary = "#6fa4d7";
	/** @var string */
	protected $colorSecondary = "#b0b3b8";
	/** @var string */
	protected $colorCode = "#000";

	/** @var QrCoder */
	protected $qrcoder;
	/** @var MxToSvg */
	protected $mx2svg;


	public function __construct() {
		$this->qrcoder = new QrCoder(self::EC_LEVEL_L);
		$this->mx2svg = new MxToSvg();
	}


	/**
	 * Set size of inner square.
	 *
	 * @param int|float $innerSize ~ Size of inner square.
	 * @param string|null $unit ~ Unit.
	 * @return void
	 */
	public function setInnerSize($innerSize, $unit = NULL) {
		$this->innerSize = $innerSize;
		$this->maxWidth = NULL;
		$this->maxHeight = NULL;
		if (NULL !== $unit) {
			$this->unit = $unit;
		}
	}


	/**
	 * Set width and height of the output image.
	 *
	 * @param int|float $width ~ Width.
	 * @param int|float $height ~ Height.
	 * @param string|null $unit ~ Unit.
	 * @return void
	 */
	public function setOuterSize($width, $height, $unit = NULL) {
		$this->maxWidth = $width;
		$this->maxHeight = $height;
		$this->innerSize = NULL;
		if (NULL !== $unit) {
			$this->unit = $unit;
		}
	}


	/**
	 * Set unit.
	 *
	 * @param string $unit
	 * @return void
	 */
	public function setUnit($unit) {
		$this->unit = $unit;
	}


	/**
	 * Set logo position.
	 *
	 * @param string $logoPosition
	 * @return void
	 */
	public function setLogoPosition($logoPosition) {
		$this->logoPosition = $logoPosition;
	}


	/**
	 * Set border.
	 *
	 * @param bool $showBorder
	 * @return void
	 */
	public function setBorder($showBorder) {
		$this->showBorder = $showBorder;
	}


	/**
	 * Set colors.
	 *
	 * @param string $primaryColor
	 * @param string $secondaryColor
	 * @return void
	 */
	public function setColors($primaryColor, $secondaryColor) {
		$this->colorPrimary = $primaryColor;
		$this->colorSecondary = $secondaryColor;
	}


	/**
	 * Set error correction level.
	 *
	 * @param int $ecLevel ~ Error correction level.
	 * @return void
	 */
	public function setErrorCorrectionLevel($ecLevel) {
		$this->qrcoder->setErrorCorrectionLevel($ecLevel);
	}


	/**
	 * @param $name
	 * @return string
	 */
	protected function getResource($name) {
		return file_get_contents(__DIR__ . '/../res/' . $name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	protected function includeSvg($name) {
		$res = preg_replace('~^<svg[^>]*?>(.*)</svg>$~', '${1}', $this->getResource($name));
		$res = str_replace(['{primary}', '{secondary}'], [$this->colorPrimary, $this->colorSecondary], $res);
		return $res;
	}


	/**
	 * Render QR code.
	 *
	 * @param int|float[] $pos
	 * @param int|float $size
	 * @param int $rotate
	 * @param Svg $svg
	 * @return Svg;
	 */
	protected function renderQrCode(array $pos, $size, $rotate, Svg $svg) {
		$transform = "translate(" . ($pos[0]) . "," . ($pos[1]) . ")";
		if (0 !== $rotate) {
			$transform .= " rotate(" . $rotate . "," . ($size / 2) . "," . ($size / 2) . ")";
		}
		return
			"<g transform=\"{$transform}\">" .
			((string) $svg->withSize($size, $size)) .
			"</g>";
	}


	/**
	 * Render border.
	 *
	 * @param float[] $pos
	 * @param float $size
	 * @param float $width
	 * @param bool $noLogo
	 * @return string
	 */
	protected function renderBorder(array $pos, $size, $width, $noLogo) {
		$wh = $width * 0.5;
		$x1 = $pos[0] - $wh;
		$y1 = $pos[1] - $wh;
		$x2 = $pos[0] + $size + $wh;
		$y2 = $pos[1] + $size + $wh;
		$a = $wh + $size * 0.255;
		$b = $wh + $size * 0.045;

		if ($noLogo) {
			$path = "M{$x1} {$y1}V{$y2}H{$x2}V{$y1}z";
		} elseif (self::LOGO_LEFT === $this->logoPosition) {
			$path = "M{$x1} " . ($y1 + $a) . "V{$y2}H{$x2}V{$y1}H" . ($x1 + $b);
		} elseif (self::LOGO_RIGHT === $this->logoPosition) {
			$path = "M{$x2} " . ($y1 + $a) . "V{$y2}H{$x1}V{$y1}H" . ($x2 - $b);
		} elseif (self::LOGO_TOP === $this->logoPosition) {
			$path = "M". ($x2 - $a) . " {$y1}H{$x1}V{$y2}H{$x2}V" . ($y1 + $b);
		} else /* if (self::LOGO_BOTTOM === $this->logoPosition) */ {
			$path = "M". ($x2 - $a) . " {$y2}H{$x1}V{$y1}H{$x2}V" . ($y2 - $b);
		}
		return "<path d=\"{$path}\" style=\"fill:none;stroke:{$this->colorPrimary};stroke-width:{$width};stroke-linecap:round;stroke-linejoin:round\"/>";
	}


	/**
	 * Render logo.
	 *
	 * @param float[] $pos
	 * @param float $size
	 * @param bool $mirror
	 * @param string $logo
	 * @return string
	 * @throws BsqrRendererException
	 */
	protected function renderLogo($pos, $size, $mirror, $logo) {
		if (self::LOGO_PAY === $logo) {
			$resName = "pay-logo.svg";
		} else {
			throw new BsqrRendererException("not supported");
		}
		$scalex = $scaley = $size / 100.0;
		if ($mirror) {
			$pos[0] += $size;
			$scalex *= -1.0;
		}
		return
			"<g transform=\"translate({$pos[0]}, {$pos[1]}) scale({$scalex}, {$scaley})\">" .
			$this->includeSvg($resName) .
			"</g>";
	}


	/**
	 * Render logo caption
	 *
	 * @param $pos
	 * @param $size
	 * @param $logo
	 * @return string
	 * @throws BsqrRendererException
	 */
	protected function renderCaption($pos, $size, $logo) {
		if (self::LOGO_PAY === $logo) {
			$resName = "pay-caption.svg";
		} else {
			throw new BsqrRendererException("not supported");
		}
		$scale = $size / 100.0;

		return
			"<g transform=\"translate({$pos[0]}, {$pos[1]}) scale({$scale})\">" .
			$this->includeSvg($resName) .
			"</g>";
	}


	/**
	 * Render svg image.
	 *
	 * @param string $bsqrData
	 * @param string $logo
	 * @return Svg
	 */
	public function render($bsqrData, $logo) {
		$noLogo = (self::LOGO_NONE === $logo);
		$baseSize = 1000.0;
		$logoSize = $baseSize * 0.213416;
		$captionSize = $baseSize * 0.790;
		$captionOffset = $baseSize * 0.053638;
		$bw = $baseSize * 0.0174;
		$bwh = $bw * 0.5;

		$size = [$baseSize, $baseSize];
		$basePos = [0.0, 0.0];
		$logoPos = null;
		$logoMirror = false;
		$captionPos = null;
		$qrRotate = 0;

		$logoOffset = $logoSize - $bwh;
		if ($this->showBorder) {
			$basePos[0] += $bw;
			$basePos[1] += $bw;
			$size[0] += 2 * $bw;
			$size[1] += 2 * $bw;
			$logoOffset -= $bw;
		}

		if ($noLogo) {
			//
		} elseif (self::LOGO_LEFT === $this->logoPosition) {
			$basePos[0] += $logoOffset;
			$logoPos = [0.0, $bwh];
			$logoMirror = true;
			$size[0] += $logoOffset;
			$qrRotate = 180;
		} elseif (self::LOGO_RIGHT === $this->logoPosition) {
			$logoPos = [$basePos[0] + $baseSize - $bwh, $bwh];
			$size[0] += $logoOffset;
			$qrRotate = 270;
		} elseif (self::LOGO_TOP === $this->logoPosition) {
			$basePos[1] += $logoOffset;
			$logoPos = [$basePos[0] + $baseSize - $logoSize + $bwh, $bwh];
			$size[1] += $logoOffset;
			$qrRotate = 270;
		} else /* if (self::LOGO_BOTTOM === $this->logoPosition) */ {
			$logoPos = [$basePos[0] + $baseSize - $logoSize + $bwh, $basePos[1] + $baseSize - $bwh];
			$captionPos = [$logoPos[0] - $captionSize, $logoPos[1] + $captionOffset]; // todo
			$size[1] += $logoOffset;
		}

		$content = "";
		if (NULL !== $bsqrData) {
			$matrix = $this->qrcoder->encode($bsqrData);
			$qrSvg = $this->mx2svg->render($matrix);
			$n = $matrix->getColumns();
			$qrSize = $baseSize * $n / ($n + 8);
			$qOffset = $baseSize * 4 / ($n + 8);
			$qrPos = [$basePos[0] + $qOffset, $basePos[1] + $qOffset];
			$content .= $this->renderQrCode($qrPos, $qrSize, $qrRotate, $qrSvg);
		}
		if ($this->showBorder) {
			$content .= $this->renderBorder($basePos, $baseSize, $bw, $noLogo);
		}
		if (!$noLogo) {
			$content .= $this->renderLogo($logoPos, $logoSize, $logoMirror, $logo);
			$content .= $this->renderCaption($captionPos, $captionSize, $logo);
		}
		return new Svg($content, ["viewBox" => "0 0 {$size[0]} {$size[1]}"]);
	}

}



/**
 *
 */
class BsqrRendererException extends \Exception { }
