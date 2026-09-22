package util

func DerefInt(p *int) int {
	if p == nil {
		return 0
	}
	return *p
}
func DerefBool(p *bool) bool {
	if p == nil {
		return false
	}
	return *p
}
func DerefString(p *string) string {
	if p == nil {
		return ""
	}
	return *p
}
func DerefIntAny[T ~int | ~int32 | ~int64](p *T) int {
	if p == nil {
		return 0
	}
	return int(*p)
}
