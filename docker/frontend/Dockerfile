FROM node:16-bullseye-slim AS build

WORKDIR /app

ARG VITE_API_BASE_URL=/service
ARG VITE_CHATBOT_URL=
ARG VITE_FOOTER=
ARG VITE_FOOTER_BY=
ARG VITE_MAPBOX_ACCESS_TOKEN=
ARG VITE_NAVIGASI=
ARG VITE_PROJECT=
ARG VITE_SOCKET_ON=true
ARG VITE_SOCKET_URL=
ARG VITE_TURNSTILE_SITE_KEY=

COPY frontend-v2/package.json frontend-v2/package-lock.json ./

RUN npm ci --legacy-peer-deps

COPY frontend-v2/ ./

# Existing frontend calls concatenate endpoint paths, so normalize the API prefix
# to one trailing slash while keeping the public build argument default `/service`.
RUN VITE_API_BASE_URL="${VITE_API_BASE_URL%/}/" \
    VITE_CHATBOT_URL="${VITE_CHATBOT_URL}" \
    VITE_FOOTER="${VITE_FOOTER}" \
    VITE_FOOTER_BY="${VITE_FOOTER_BY}" \
    VITE_MAPBOX_ACCESS_TOKEN="${VITE_MAPBOX_ACCESS_TOKEN}" \
    VITE_NAVIGASI="${VITE_NAVIGASI}" \
    VITE_PROJECT="${VITE_PROJECT}" \
    VITE_SOCKET_ON="${VITE_SOCKET_ON}" \
    VITE_SOCKET_URL="${VITE_SOCKET_URL}" \
    VITE_TURNSTILE_SITE_KEY="${VITE_TURNSTILE_SITE_KEY}" \
    npm run build

FROM nginx:1.27-alpine AS production

COPY docker/frontend/default.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist/ /usr/share/nginx/html/
RUN chmod -R a+rX /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
