package repo

import (
	"reflect"
	"testing"
)

func TestScopeLabels(t *testing.T) {
	tests := []struct {
		name  string
		scope string
		want  []string
	}{
		{name: "kosong", scope: "  ", want: nil},
		{name: "suhu aliases", scope: " SUHU ", want: []string{"suhu", "suhu & kelembaban", "suhu & kelembapan"}},
		{name: "dimensi aliases", scope: "dimensi", want: []string{"dimensi", "gaya"}},
		{name: "fallback dinormalisasi", scope: "Torsi_Besar", want: []string{"torsi besar"}},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if got := scopeLabels(tt.scope); !reflect.DeepEqual(got, tt.want) {
				t.Fatalf("scopeLabels(%q) = %#v, ingin %#v", tt.scope, got, tt.want)
			}
		})
	}
}
