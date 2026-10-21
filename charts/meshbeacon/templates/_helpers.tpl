{{/*
Chart fullname - prefixes every resource name, truncated to fit DNS-1123
label limits per standard Helm chart conventions.
*/}}
{{- define "meshbeacon.fullname" -}}
{{- if .Release.Name | eq .Chart.Name }}
{{- .Chart.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name .Chart.Name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}

{{/*
Common labels.
*/}}
{{- define "meshbeacon.labels" -}}
helm.sh/chart: {{ printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" }}
app.kubernetes.io/name: {{ .Chart.Name }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels for a given component, e.g. "app", "mqtt-worker".
*/}}
{{- define "meshbeacon.selectorLabels" -}}
app.kubernetes.io/name: {{ .Chart.Name }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/component: {{ .component }}
{{- end }}

{{/*
Name of the Secret holding .env secrets - either the chart-managed one or a
user-supplied existingSecret.
*/}}
{{- define "meshbeacon.secretName" -}}
{{- if .Values.secrets.existingSecret }}
{{- .Values.secrets.existingSecret }}
{{- else }}
{{- printf "%s-secrets" (include "meshbeacon.fullname" .) }}
{{- end }}
{{- end }}

{{/*
In-cluster MQTT broker host (used for MQTT_HOST in the app ConfigMap).
*/}}
{{- define "meshbeacon.mqttHost" -}}
{{- printf "%s-mqtt-broker.%s.svc.cluster.local" (include "meshbeacon.fullname" .) .Release.Namespace }}
{{- end }}
