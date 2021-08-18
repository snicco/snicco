TITLE:{{$title ?? 'FAILED:NO SLOT WAS PASSED'}},
CONTENT:{{ strtoupper($slot) }}
@isset($scoped)
	,SCOPED:{{$scoped}}
@endisset