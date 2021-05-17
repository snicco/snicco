@props(['type','message'])

ID:{{$attributes['id']}},
CLASS:{{$attributes['class']}},
MESSAGE:{{$attributes['message'] ?'FAILED: MESSAGE_WAS_NOT_MOVED_TO_PROPS' : $message}},
TYPE:{{$attributes['type'] ?'FAILED: ERROR_WAS_NOT_MOVED_TO_PROPS' : $type}}
