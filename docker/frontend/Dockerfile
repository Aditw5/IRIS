FROM node:16-bullseye-slim AS build

WORKDIR /app

ARG VITE_API_BASE_URL=/service
ARG VITE_SOCKET_URL=
ARG VITE_SOCKET_ON=true

COPY frontend-v2/package.json frontend-v2/package-lock.json ./

RUN npm ci --legacy-peer-deps

COPY frontend-v2/ ./

# Existing frontend calls concatenate endpoint paths, so normalize the API prefix
# to one trailing slash while keeping the public build argument default `/service`.
RUN VITE_API_BASE_URL="${VITE_API_BASE_URL%/}/" \
    VITE_SOCKET_URL="${VITE_SOCKET_URL}" \
    VITE_SOCKET_ON="${VITE_SOCKET_ON}" \
    npm run build

FROM nginx:1.27-alpine AS production

COPY docker/frontend/default.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist/ /usr/share/nginx/html/

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

